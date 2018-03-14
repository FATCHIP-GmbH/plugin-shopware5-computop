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

use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Fatchip\CTPayment\CTOrder\CTOrder;

// add baseclass via require_once so we can extend
// ToDo find a better solution for this
require_once 'FatchipCTPayment.php';


/**
 * Class Shopware_Controllers_Frontend_FatchipCTKlarna
 */
class Shopware_Controllers_Frontend_FatchipCTKlarna extends \Shopware_Controllers_Frontend_FatchipCTPayment
{

    public $paymentClass = 'Klarna';

    /**
     * @inheritdoc
     */
    public function gatewayAction()
    {
        $orderVars = $this->session->sOrderVariables;
        $userData = $orderVars['sUserData'];

        $test = $this->getUser();

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($this->getAmount() * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($userData['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($userData['shippingaddress']));
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);
        // Mandatory for paypalStandard
        $ctOrder->setOrderDesc($this->getOrderDesc());

        $usesInvoice = ($userData['additional']['payment']['name'] === 'fatchip_computop_klarna_invoice');
        $isFirm = !empty($userData['billingaddress']['company']);
        $invoice = $usesInvoice ? '-1' : '1334';

        /** @var \Fatchip\CTPayment\CTPaymentMethodsIframe\Klarna $payment */
        $payment = $this->paymentService->getIframePaymentClass(
            $this->paymentClass,
            $this->config,
            $ctOrder,
            null,
            null,
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserData(),
            null,
            $isFirm,
            $invoice
        );

        $payment->setSocialSecurityNumber($this->utils->getUserSSN($userData));
        $payment->setAnnualSalary($this->utils->getUserAnnualSalary($userData));
        $payment->setPhone($this->utils->getUserPhone($userData));
        $payment->setDateOfBirth($this->utils->getUserDoB($userData));
        $requestParams = $payment->getRedirectUrlParams();
        $response = $this->plugin->callComputopService($requestParams, $payment, 'Klarna', $payment->getCTPaymentURL());

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
            //careful: $position['amount'] contains the total for the position, so QTY*Price
            //in controllers/backend/FatchipCTOrder $position->getPrice() returns price of only 1 article
            $orderDesc .= $position['quantity'] . ';' . $position['articleID'] . ';' . $position['articlename'] . ';'
              . $position['amount'] * 100 / $position['quantity'] . ';' . $position['tax_rate'] . ';0;0';
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
