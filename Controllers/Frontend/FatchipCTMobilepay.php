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
 * Class Shopware_Controllers_Frontend_FatchipCTMobilepay
 */
class Shopware_Controllers_Frontend_FatchipCTMobilepay extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    public $paymentClass = 'Mobilepay';

    public function getPaymentClass($order) {
        $router = $this->Front()->Router();

        return new \Fatchip\CTPayment\CTPaymentMethodsIframe\Mobilepay(
          $this->config,
          $order, $router->assemble(['action' => 'success', 'forceSecure' => true]),
          $router->assemble(['action' => 'failure', 'forceSecure' => true]),
          $router->assemble(['action' => 'notify', 'forceSecure' => true]),
          $this->getOrderDesc(),
          $this->getUserData());
    }

}
