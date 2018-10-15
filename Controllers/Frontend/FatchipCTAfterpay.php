<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

require_once 'FatchipCTPayment.php';

use Fatchip\CTPayment\CTEnums\CTEnumStatus;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTAfterpay
 *
 * Frontend controller for Lastschrift
 *
 * @category   Payment_Controller
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */
class Shopware_Controllers_Frontend_FatchipCTAfterpay extends Shopware_Controllers_Frontend_FatchipCTPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'Afterpay';

    /**
     * GatewaAction is overridden for Lastschrift because there is no redirect
     * but a server to server call is made
     *
     * On success create the order and forward to checkout/finish
     * On failure forward to checkout/payment and set the error message
     *
     * @return void
     */
    public function gatewayAction()
    {
        $payment = $this->getPaymentClassForGatewayAction();
        $orderData = $this->session->sOrderVariables;
        $user = $orderData['sUserData'];
        $basket = $this->get('modules')->Basket()->sGetBasket();
        $paymentName = $user['additional']['payment']['name'];

        $test = $payment->setOrder($basket);

        // TODO add and Test AbocOmmerce Support
        /* if ($this->utils->isAboCommerceArticleInBasket()) {
            $payment->setMdtSeqType('FRST');
        }
        */

        // TODO implement optional parameters
        // $payment->setAccBank($this->utils->getUserLastschriftBank($user));
        // $payment->setAccOwner($this->utils->getUserLastschriftKontoinhaber($user));
        // $payment->setIBAN($this->utils->getUserLastschriftIban($user));

        $requestParams = $payment->getRedirectUrlParams();
        $requestParams['bdEmail'] = $user['additional']['user']['email'];
        $requestParams['Email'] = $user['additional']['user']['email'];
        $requestParams['CompanyOrPerson'] = 'Person';
        // Mandatory ????
        $requestParams['DateOfBirth'] = '1977-12-12';
         // unset($requestParams['sdZip']);
         unset($requestParams['EtiId']);
         unset($requestParams['userData']);


        switch ($paymentName) {
            case 'fatchip_computop_afterpay_invoice':
                $requestParams['PayType'] = 'Invoice';
                break;

            /* TODO
            case 'fatchip_computop_afterpay_account':
                $requestParams['PayType'] = 'Account';
                break;
            */
            case 'fatchip_computop_afterpay_installment':
                $requestParams['PayType'] = 'Installment';
                $requestParams['ProductNr'] = $this->session->get('FatchipComputopAfterpayProductNr');
                $requestParams['IBAN'] = $this->utils->getUserAfterpayInstallmentIban($user);
                break;

            /* TODO
            case 'fatchip_computop_afterpay_consolidatedinvoice':
                $requestParams['PayType'] = 'ConsolidatedInvoice';
                $requestParams['InvoiceDate'] = '2018-08-16';
                break;
            */
        }

        $response = $this->plugin->callComputopService($requestParams, $payment, 'AFTERPAY', $payment->getCTPaymentURL());

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResult($response);

                $this->session->offsetUnSet('FatchipComputopAfterpayProductNr');

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $ctError = [];
                $ctError['CTErrorMessage'] = self::ERRORMSG; // . $response->getDescription();
                $ctError['CTErrorCode'] = ''; //$response->getCode();
                $this->forward('shippingPayment', 'checkout', null, array('CTError' => $ctError));

                break;
        }
    }

}

