<?php

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
 * PHP version 5.6, 7 , 7.1
 *
 * @category  Payment
 * @package   Computop_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 Computop
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.computop.com
 */


use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;

require_once 'FatchipCTPayment.php';


/**
 * Class Shopware_Controllers_Frontend_FatchipCTPaypalStandard
 */
class Shopware_Controllers_Frontend_FatchipCTPaypalExpress extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    const paymentClass = 'PaypalExpress';

    public function indexAction()
    {
        $this->forward('confirm');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $basket= Shopware()->Modules()->Basket()->sGetBasket();

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($basket['AmountNumeric'] * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        // Mandatory for paypalStandard
        $ctOrder->setOrderDesc($this->getOrderDesc());

        /** @var \Fatchip\CTPayment\CTPaymentMethodsIframe\PaypalStandard $payment */
        $payment = $this->paymentService->getIframePaymentClass(
            'PaypalStandard',
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'return', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            'Test',
            $this->getUserData()
            //$this->getOrderDesc()
        );

        $payment->setPayPalMethod('shortcut');
        $payment->setNoShipping(0);
        $params = $payment->getRedirectUrlParams();

        $this->redirect($payment->getHTTPGetURL($params));
    }

    /**
     * Cancel action method
     * @return void
     */
    public function returnAction()
    {
        $requestParams = $this->Request()->getParams();
        $session = Shopware()->Session();

        $response = $this->paymentService->getDecryptedResponse($requestParams);

        switch ($response->getStatus()) {
            case CTEnumStatus::AUTHORIZE_REQUEST;
                $session->offsetSet('FatchipCTPaypalExpressPayID', $response->getPayID() );
                $session->offsetSet('FatchipCTPaypalExpressXID', $response->getXID());
                $session->offsetSet('FatchipCTPaypalExpressTransID', $response->getXID());

                // forward to PP Express register Controller to login the User with an
                // "Schnellbesteller" Account

                $this->forward('register', 'FatchipCTPaypalExpressRegister', null, [ 'CTResponse' => $response]);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * success action method
     * @return void
     * @throws Exception
     */
    public function confirmAction()
    {
        $session = Shopware()->Session();
        $orderVars = Shopware()->Session()->sOrderVariables;
        $userData = $orderVars['sUserData'];

        /** @var \Fatchip\CTPayment\CTPaymentMethods\PaypalExpress $payment */
        $payment = $this->paymentService->getPaymentClass(self::paymentClass, $this->config);

        $requestParams =  $payment->getPaypalExpressCompleteParams(
            $session->offsetGet('FatchipCTPaypalExpressPayID'),
            $session->offsetGet('FatchipCTPaypalExpressTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName()
        );
        // wrap this in a method we can hook for central logging
        // refactor Amazon to use central Paymentservice to get rid of service Param
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
     * @return void
     * Cancel action method
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $ctError = [];

        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $ctError['CTErrorMessage'] = self::ERRORMSG . $response->getDescription();
        $ctError['CTErrorCode'] = $response->getCode();
        return $this->forward('shippingPayment', 'checkout', null, ['CTError' => $ctError]);
    }
}


