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

/**
 * Class Shopware_Controllers_Frontend_FatchipCTSofort
 */

require_once 'FatchipCTPayment.php';

class Shopware_Controllers_Frontend_FatchipCTSofort extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    public $paymentClass = 'Sofort';

    public function gatewayAction()
    {
        $session = Shopware()->Session();
        $orderVars = Shopware()->Session()->sOrderVariables;
        $userData = $orderVars['sUserData'];

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($this->getAmount() * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($userData['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($userData['shippingaddress']));
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);

        /** @var \Fatchip\CTPayment\CTPaymentMethodsIframe\Ideal $payment */
        $payment = $this->getPaymentClass($ctOrder);
        $payment->setIssuerID($session->offsetGet('FatchipComputopSofortIssuer'));

        $this->redirect($payment->getHTTPGetURL());
    }

    public function getPaymentClass($order) {
        $router = $this->Front()->Router();

        return $this->paymentService->getPaymentClass(
            $this->paymentClass,
            $this->config,
            $order,
            $router->assemble(['action' => 'success', 'forceSecure' => true]),
            $router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserData()
        );
    }

}
