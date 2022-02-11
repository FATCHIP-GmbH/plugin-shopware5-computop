<?php

/**
 * The First Cash Solution Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The First Cash Solution Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with First Cash Solution Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */

require_once 'FatchipFCSPayment.php';

use Fatchip\FCSPayment\CTOrder\CTOrder;
use Fatchip\FCSPayment\CTEnums\CTEnumStatus;
use Fatchip\FCSPayment\CTPaymentMethods\PaypalExpress;
use Fatchip\FCSPayment\CTPaymentMethodsIframe\PaypalStandard;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSPaypalStandard
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */
class Shopware_Controllers_Frontend_FatchipFCSPaypalExpress extends Shopware_Controllers_Frontend_FatchipFCSPayment
{

    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'PaypalExpress';

    /**
     * Forwards to confirm action
     *
     * @return void
     */
    public function indexAction()
    {
        $this->forward('confirm');
    }

    /**
     * Gateway action method.
     *
     * User is redirected here after clicking on the paypal express checkout button
     * Redirects the user to the paypal website
     *
     * @throws Exception
     * @return void
     */
    public function gatewayAction()
    {
        $basket= Shopware()->Modules()->Basket()->sGetBasket();

        // TODO refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($basket['AmountNumeric'] * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        // mandatory for paypalStandard
        $ctOrder->setOrderDesc($this->getOrderDesc());

        /** @var PaypalStandard $payment */
        $payment = $this->paymentService->getIframePaymentClass(
            'PaypalStandard',
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'return', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            'Test',
            $this->getUserDataParam()
        );

        $payment->setPayPalMethod('shortcut');
        $payment->setNoShipping(0);
        $params = $payment->getRedirectUrlParams();

        $this->redirect($payment->getHTTPGetURL($params));
    }

    /**
     * Return action method.
     *
     * User is redirected here after returning from the paypal site
     * Forwards user to paypalExpressRegister or to failure Action
     *
     * @return void
     */
    public function returnAction()
    {
        $requestParams = $this->Request()->getParams();
        $session = Shopware()->Session();
        $response = $this->paymentService->getDecryptedResponse($requestParams);

        switch ($response->getStatus()) {
            case CTEnumStatus::AUTHORIZE_REQUEST;
                $session->offsetSet('FatchipFCSPaypalExpressPayID', $response->getPayID());
                $session->offsetSet('FatchipFCSPaypalExpressXID', $response->getXID());
                $session->offsetSet('FatchipFCSPaypalExpressTransID', $response->getXID());

                $this->forward('register', 'FatchipFCSPaypalExpressRegister', null, [ 'CTResponse' => $response]);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Success action method.
     *
     * Called after First Cash Solution redirects to SuccessURL
     * Order is created with status paid, transactionIDs are saved,
     * RefNr is updated and user is redirected to finish page
     *
     * @return void
     * @throws Exception
     */
    public function confirmAction()
    {
        $session = Shopware()->Session();

        /** @var PaypalExpress $payment */
        $payment = $this->paymentService->getPaymentClass($this->paymentClass);

        $requestParams =  $payment->getPaypalExpressCompleteParams(
            $session->offsetGet('FatchipFCSPaypalExpressPayID'),
            $session->offsetGet('FatchipFCSPaypalExpressTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName()
        );

        $requestParams['EtId'] = $this->getUserDataParam();

        // ORDER call
        $response = $this->plugin->callComputopService($requestParams, $payment, 'ORDER', $payment->getCTPaymentURL());

        if ($response->getStatus() !== CTEnumStatus::OK){
            // TODO: add error log
            $this->forward('failure');
        }

        $orderNumber = $this->saveOrder(
            $response->getTransID(),
            $response->getPayID(),
            self::PAYMENTSTATUSRESERVED
        );

        $this->saveTransactionResult($response);

        $customOrdernumber = $this->customizeOrdernumber($orderNumber);

        // REFNRCHANGE call
        $response = $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);

        if ($response->getStatus() !== CTEnumStatus::OK){
            // TODO: add error log
            $this->savePaymentStatus(
                $response->getTransID(),
                $response->getPayID(),
                self::PAYMENTSTATUSREVIEWNECESSARY
            );
            $this->forward('failure');
        }

        // autoCapture handles errors during capture itself
        $this->autoCapture($customOrdernumber);

        $this->forward('finish', 'FatchipFCSPaypalExpressCheckout', null, array('sUniqueID' => $response->getPayID()));
    }

    /**
     * Cancel action method.
     *
     * If an error occurs in the First Cash Solution call or user cancels on paypal page user is redirected here
     * Reads error message from response and redirects to shippingpayment page
     *
     * @return void
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $ctError = [];
        $ctError['CTErrorMessage'] = Shopware()->Snippets()
            ->getNamespace('frontend/FatchipFCSPayment/translations')
            ->get('errorGeneral'); // . $response->getDescription();
        $ctError['CTErrorCode'] = ''; //$response->getCode();
        $this->forward('shippingPayment', 'checkout', null, ['FCSError' => $ctError]);
    }
}


