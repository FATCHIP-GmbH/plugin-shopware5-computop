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
 * Class Shopware_Controllers_Frontend_FatchipCTLastschrift
 */


class Shopware_Controllers_Frontend_FatchipCTLastschrift extends Shopware_Controllers_Frontend_FatchipCTPayment
{
    public $paymentClass = '';

    /**
     * init payment controller
     */
    public function init()
    {
        parent::init();
        switch ($this->config['lastschriftDienst']) {
            case 'DIREKT':
                $this->paymentClass = 'LastschriftDirekt';
                break;
            case 'EVO':
                $this->paymentClass = 'LastschriftEVO';
                break;
            case 'INTERCARD':
                $this->paymentClass = 'LastschriftInterCard';
                break;
        }
    }

    public function gatewayAction()
    {
        // we have to use this, because there is no order yet
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $amount = $this->getAmount();


        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($amount * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($user['shippingaddress']));
        // Sw 5.04 user email
        // check other versions
        $ctOrder->setEmail($user['additional']['user']['email']);
        $ctOrder->setCustomerID($user['additional']['user']['id']);

        /** @var \Fatchip\CTPayment\CTPaymentMethodsIframe\Lastschrift $payment */
        $payment = $this->paymentService->getIframePaymentClass(
          $this->paymentClass,
          $this->config,
          $ctOrder,
          $this->router->assemble(['action' => 'return', 'forceSecure' => true]),
          $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
          $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
          $this->getOrderDesc(),
          $this->getUserData()
        );

        $payment->setAccBank($this->utils->getUserLastschriftBank($user));
        $payment->setAccOwner($this->utils->getUserLastschriftKontoinhaber($user));
        $payment->setIBAN($this->utils->getUserLastschriftIban($user));

        $requestParams = $payment->getRedirectUrlParams();
        $response = $this->plugin->callComputopService($requestParams, $payment, 'Lastschrift', $payment->getCTPaymentURL());

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber =  $this->saveOrder(
                  $response->getTransID(),
                  $response->getPayID(),
                  self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResult($response);
                $this->updateRefNrWithComputopFromOrderNumber($orderNumber);
                //$this->redirect(['controller' => 'checkout', 'action' => 'finish', ['sAGB' => 'true']]);
                $params = $this->Request()->getParams();
                $this->forward('finish', 'checkout', null, ['sAGB' => 1]);
                break;
            default:
                $ctError = [];
                $ctError['CTErrorMessage'] = self::ERRORMSG . $response->getDescription();
                $ctError['CTErrorCode'] = $response->getCode();
                return $this->forward('shippingPayment', 'checkout', null, array('CTError' => $ctError));

                break;
        }
    }
}


