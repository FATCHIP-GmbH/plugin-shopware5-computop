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

// add baseclass via require_once so we can extend
// ToDo find a better solution for this
require_once 'FatchipCTPayment.php';

use Fatchip\CTPayment\CTOrder\CTOrder;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTIdeal
 *
 * Direkt
 * SW 5.0 - check
 * SW 5.1 -
 * SW 5.2 -
 * SW 5.3 -
 * Sofort
 * SW 5.0 -
 * SW 5.1 -
 * SW 5.2 -
 * SW 5.3 -
 */
class Shopware_Controllers_Frontend_FatchipCTIdeal extends Shopware_Controllers_Frontend_FatchipCTPayment
{
    public $paymentClass = 'Ideal';

    /**
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $user = Shopware()->Modules()->Admin()->sGetUserData();

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($this->getAmount() * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($user['shippingaddress']));
        $ctOrder->setEmail($user['additional']['user']['email']);

        /** @var \Fatchip\CTPayment\CTPaymentMethodsIframe\Ideal $payment */
        $payment = $this->getPaymentClass($ctOrder);
        // ToDO how to handle issuer? from backend Config?
        // for now just use the first one from db model
        /** @var \Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers $issuer */
        $issuer = Shopware()->Models()->find('Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers',1);
        $payment->setIssuerID($issuer->getIssuerId());

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
