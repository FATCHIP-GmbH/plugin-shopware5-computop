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


/**
 * Class Shopware_Controllers_Frontend_FatchipCTKlarna
 */
class Shopware_Controllers_Frontend_FatchipCTKlarna extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    public $paymentClass = 'Klarna';

    public function getPaymentClass($config, $order) {
        $router = $this->Front()->Router();

        return new \Fatchip\CTPayment\CTPaymentMethodsIframe\Klarna(
            $config,
            $order,
            $router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserData(),
            'stefnq@sdflj.de',  //TODO
            '030 34989384938', //TODO
            '01511 838757577', //TODO
            '1960-07-07', //TODO
            'M', //TODO
            FALSE, //TODO
            '-1'
        );
    }

    public function getOrderDesc() {
        //TODO: Implementieren
        return '2;1234;TestProdukt;450;19;0;0';
    }

}
