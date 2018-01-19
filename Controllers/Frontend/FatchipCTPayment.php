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

/**
 * Class Shopware_Controllers_Frontend_FatchipCTPayment
 */

use Shopware\FatchipCTPayment\Util;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;


abstract class Shopware_Controllers_Frontend_FatchipCTPayment extends Shopware_Controllers_Frontend_Payment
{

    const PAYMENTSTATUSPAID = 12;

    /** @var \Fatchip\CTPayment\CTPaymentService $service */
    protected $paymentService = null;

    public $paymentClass = '';

    protected $plugin;

    protected $config;

    /**
     * init payment controller
     */
    public function init()
    {
        // ToDo handle possible Exception
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
    }

    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['notify'];
    }

    /**
     *
     */
    public function preDispatch()
    {
    }

    /**
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $user = $this->getUser();
        $util = new Util();

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($this->getAmount() * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($util->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($util->getCTAddress($user['shippingaddress']));
        $ctOrder->setEmail($user['additional']['user']['email']);
        // Mandatory for paypalStandard
        $ctOrder->setOrderDesc('TestBestellung');


        $payment = $this->getPaymentClass($ctOrder);

        $this->redirect($payment->getHTTPGetURL());
    }


    /**
     * @return void
     * Cancel action method
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $session = Shopware()->Session();
        $ctError = [];

        $response = $this->paymentService->createPaymentResponse($requestParams);

        $ctError['CTErrorMessage'] = $response->getDescription();
        $ctError['CTErrorCode'] = $response->getCode();

        // remove easycredit session var
        $session->offsetSet('fatchipComputopEasyCreditPayId', null);

        return $this->forward('shippingPayment', 'checkout', null, array('CTError' => $ctError));
    }

    /**
     * Cancel action method
     * @return void
     */
    public function successAction()
    {
        $requestParams = $this->Request()->getParams();
        $user = $this->getUser();

        /** @var CTResponseCreditCard $response */
        $response = $this->paymentService->createPaymentResponse($requestParams);
        $token = $this->paymentService->createPaymentToken($this->getAmount(), $user['billingaddress']['customernumber']);

        if (!$this->paymentService->isValidToken($response, $token)) {
            $this->forward('failure');
            return;
        }
        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $this->saveOrder(
                    $response->getTransID(),
                    $response->getUserData(),
                    self::PAYMENTSTATUSPAID
                );
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    public function getOrderDesc($order) {
        //TODO: Implementieren
        return 'ORderDesc';
    }

    public function getUserData() {
        // ToDo should this be done in the CTPaymentService?
        $user = $this->getUser();
        return $this->paymentService->createPaymentToken($this->getAmount(), $user['billing']['customernumber']);
    }

    public function getPaymentClass($order) {
        $router = $this->Front()->Router();

        $userData = $this->getUserData();

        return $this->paymentService->getPaymentClass(
             $this->paymentClass,
             $this->config,
             $order,
             $router->assemble(['action' => 'success', 'forceSecure' => true]),
             $router->assemble(['action' => 'failure', 'forceSecure' => true]),
             $router->assemble(['action' => 'notify', 'forceSecure' => true]),
             'orderdesc', //todo
             $userData);

    }

}
