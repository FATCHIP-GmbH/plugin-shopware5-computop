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

    public function getPaymentClass($order) {
        $router = $this->Front()->Router();

        $user = Shopware()->Modules()->Admin()->sGetUserData();

        $phone = $this->utils->getUserPhone($user);
        $birthday =$this->utils->getUserDoB($user);
        $isFirm = !empty($user['billingaddress']['company']);

        return new \Fatchip\CTPayment\CTPaymentMethodsIframe\Klarna(
            $this->config,
            $order,
            $router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserData(),
            $phone,
            $phone, //TODO remove mobile
            $birthday,
            $isFirm,
            '-1'
        );
    }

    /**
     * Beschreibung der gebuchten Artikel:
     * Menge, ArtikelNr, Bezeichnung, Preis, Arti-kelkennung. Rabatt und MwSt. als Prozentzahl angeben.
     * Felder durch ";" und Rechnungspositionen durch "+" trennen.
     * Preise ohne Komma in kleinster Wäh-rungseinheit angeben:
     * <qty>;<artno>; <title>; <price>; <vat>;<discount>;<Artic-leFlag> +
     *
     * Beispiel: 25;12345;Kugelschreiber;890;19;1.5;0 + 1;11223;Versandkosten;490;19;0;8
     *
     * Werte und Wirkung des <ArticleFlag>:
     * <0> keine Kennzeichnung,
     * <1> Mengen-angabe in 1/1000,
     * <2> Menge in 1/100,
     * <4> Menge in 1/10,
     * <8> Artikel ist eine Versandgebühr,
     * <16> Artikel ist eine Bearbeitungsgebühr,
     * <32> Preisangabe erfolgt inkl. MwSt.
     *
     * @return string
     */
    public function getOrderDesc() {
        $basket =  $this->getBasket();
        $orderDesc = '';
        foreach($basket['content'] as $position) {
            if (!empty($orderDesc)) {
                $orderDesc .= ' + ';
            }
            $orderDesc .= $position['quantity'] . ';' . $position['articleID'] . ';' . $position['articlename'] . ';'
              . $position['amount'] * 100 . ';' . $position['tax_rate'] . ';0;0';
        }
        //add shipping if > 0
        if ($basket['sShippingcosts'] != 0) {
            if (!empty($orderDesc)) {
                $orderDesc .= ' + ';
            }
            $orderDesc .= '1;shipping;Versandkosten;' . $basket['sShippingcosts'] * 100 . ';' . $basket['sShippingcostsTax'] . ';0;8';
        }

        return $orderDesc;
    }

}
