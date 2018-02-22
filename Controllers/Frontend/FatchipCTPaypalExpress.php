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
// add baseclass via require_once so we can extend
// ToDo find a better solution for this
require_once 'FatchipCTPayment.php';


/**
 * Class Shopware_Controllers_Frontend_FatchipCTPaypalStandard
 */
class Shopware_Controllers_Frontend_FatchipCTPaypalExpress extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    public $paymentClass = 'PaypalStandard';


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
        // ToDo Check sGEtBAsket  availablity in all SW Versions
        $basket= Shopware()->Modules()->Basket()->sGetBasket();

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($basket['AmountNumeric'] * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        // Mandatory for paypalStandard
        $ctOrder->setOrderDesc($this->getOrderDesc());

        /*  @var \Fatchip\CTPayment\CTPaymentMethodsIframe\PaypalStandard $payment */
        $payment = $this->getPaymentClass($ctOrder, 'return');
        $payment->setPayPalMethod('shortcut');

        $this->redirect($payment->getHTTPGetURL());
    }

    /**
     * Cancel action method
     * @return void
     */
    public function returnAction()
    {
        $requestParams = $this->Request()->getParams();
        $session = Shopware()->Session();
        // ToDo Check sGetBasket  availablity in all SW Versions
        $basket= Shopware()->Modules()->Basket()->sGetBasket();

        /** @var CTResponseFatchipCTKlarnaCreditCard $response */
        $response = $this->paymentService->createPaymentResponse($requestParams);
        // ToDo token is broken, for PP Ex. because getAmount is only available after User Registration
        $token = $this->paymentService->createPaymentToken($this->getAmount(), $this->utils->getUserCustomerNumber($this->getUser()));

        switch ($response->getStatus()) {
            case CTEnumStatus::AUTHORIZE_REQUEST;
                $session->offsetSet('FatchipCTPayPalExpressPayID', $response->getPayID() );
                $session->offsetSet('FatchipCTPayPalExpressXID', $response->getXID());

                // forward to PP Express register Controller to login the User with an
                // "Schnellbesteller" Account

                $this->forward('register', 'FatchipCTPaypalExpressRegister', null, [ 'CTResponse' => $response]);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }



    public function getPaymentClass($order, $successAction = '') {
        $router = $this->Front()->Router();

        return new \Fatchip\CTPayment\CTPaymentMethodsIframe\PaypalStandard(
            $this->config,
            $order,
            $router->assemble(['action' => $successAction, 'forceSecure' => true]),
            $router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getUserData(),
            $this->getOrderDesc()
        );
    }

}


