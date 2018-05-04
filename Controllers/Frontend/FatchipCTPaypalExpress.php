<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The Computop Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The Computop Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Computop Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

require_once 'FatchipCTPayment.php';

use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTPaypalStandard
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */
class Shopware_Controllers_Frontend_FatchipCTPaypalExpress extends Shopware_Controllers_Frontend_FatchipCTPayment
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

        $payment = $this->paymentService->getIframePaymentClass(
            'PaypalStandard',
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'return', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            'Test',
            $this->getUserDataParam()
            //$this->getOrderDesc()
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
                $session->offsetSet('FatchipCTPaypalExpressPayID', $response->getPayID());
                $session->offsetSet('FatchipCTPaypalExpressXID', $response->getXID());
                $session->offsetSet('FatchipCTPaypalExpressTransID', $response->getXID());

                $this->forward('register', 'FatchipCTPaypalExpressRegister', null, [ 'CTResponse' => $response]);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Success action method.
     *
     * Called after Computop redirects to SuccessURL
     * Order is created with status paid, transactionIDs are saved,
     * RefNr is updated and user is redirected to finish page
     *
     * @return void
     * @throws Exception
     */
    public function confirmAction()
    {
        $session = Shopware()->Session();
        $payment = $this->paymentService->getPaymentClass($this->paymentClass, $this->config);

        $requestParams =  $payment->getPaypalExpressCompleteParams(
            $session->offsetGet('FatchipCTPaypalExpressPayID'),
            $session->offsetGet('FatchipCTPaypalExpressTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName()
        );
        $requestParams['EtId'] = $this->getUserDataParam();

        $response = $this->plugin->callComputopService($requestParams, $payment, 'ORDER', $payment->getCTPaymentURL());
        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSPAID
                );
                $this->saveTransactionResult($response);
                $this->updateRefNrWithComputopFromOrderNumber($orderNumber);
                $this->redirect(['controller' => 'FatchipCTPaypalExpressCheckout', 'action' => 'finish']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Cancel action method.
     *
     * If an error occurs in the Computop call or user cancels on paypal page user is redirected here
     * Reads error message from response and redirects to shippingpayment page
     *
     * @return void
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $ctError = [];
        $ctError['CTErrorMessage'] = self::ERRORMSG; // . $response->getDescription();
        $ctError['CTErrorCode'] = ''; //$response->getCode();
        $this->forward('shippingPayment', 'checkout', null, ['CTError' => $ctError]);
    }
}


