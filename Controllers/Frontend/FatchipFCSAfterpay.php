<?php

/**
 * The First Cash Solution Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The First Cash Solution Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with First Cash Solution Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */

require_once 'FatchipFCSPayment.php';

use Fatchip\CTPayment\CTEnums\CTEnumStatus;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSAfterpay
 *
 * Frontend controller for Lastschrift
 *
 * @category   Payment_Controller
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */
class Shopware_Controllers_Frontend_FatchipFCSAfterpay extends Shopware_Controllers_Frontend_FatchipFCSPayment
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
     * @throws Exception
     */
    public function gatewayAction()
    {
        $payment = $this->getPaymentClassForGatewayAction();
        $orderData = $this->session->sOrderVariables;
        $user = $orderData['sUserData'];
        $basket = $this->get('modules')->Basket()->sGetBasket();
        $paymentName = $user['additional']['payment']['name'];

        $payment->setOrder($basket);

        $requestParams = $payment->getRedirectUrlParams();
        $requestParams['DateOfBirth'] = $this->utils->getUserDoB($user);
        if (!empty($this->utils->getUserPhone($user))) {
            $requestParams['bdPhone'] = $this->utils->getUserPhone($user);
        }
        if (!empty($this->utils->getUserSSN($user))) {
            $requestParams['SocialSecurityNumber'] = $this->utils->getUserSSN($user);
        }
        unset($requestParams['EtiId']);
        unset($requestParams['userData']);


        switch ($paymentName) {
            case 'fatchip_firstcash_afterpay_invoice':
                $requestParams['PayType'] = 'Invoice';
                break;

            /* TODO
            case 'fatchip_firstcash_afterpay_account':
                $requestParams['PayType'] = 'Account';
                break;
            */
            case 'fatchip_firstcash_afterpay_installment':
                $requestParams['PayType'] = 'Installment';
                $requestParams['ProductNr'] = $this->session->get('FatchipFirstCashAfterpayProductNr');
                $requestParams['IBAN'] = $this->utils->removeWhitespaces($this->utils->getUserAfterpayInstallmentIban($user));
                break;

            /* TODO
            case 'fatchip_firstcash_afterpay_consolidatedinvoice':
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

                $this->session->offsetUnSet('FatchipFirstCashAfterpayProductNr');

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $ctError = [];
                $ctError['CTErrorMessage'] = Shopware()->Snippets()
                    ->getNamespace('frontend/FatchipFCSPayment/translations')
                    ->get('errorGeneral'); // . $response->getDescription();
                $ctError['CTErrorCode'] = ''; //$response->getCode();
                $this->session->offsetUnSet('FatchipFirstCashAfterpayProductNr');
                $this->forward('shippingPayment', 'checkout', null, array('CTError' => $ctError));

                break;
        }
    }

    /**
     * Recurring payment action method.
     */
    public function recurringAction()
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();
        $params = $this->Request()->getParams();

        if ($this->Request()->isXmlHttpRequest()) {

            $basket = $this->getBasket();
            $payment = $this->getPaymentClassForGatewayAction();
            $user = $this->getUserData();

            $payment->setOrder($basket);

            $requestParams = $payment->getRedirectUrlParams();
            $requestParams['PayType'] = 'Invoice';
            $requestParams['DateOfBirth'] = $this->utils->getUserDoB($user);
            if (!empty($this->utils->getUserPhone($user))) {
                $requestParams['bdPhone'] = $this->utils->getUserPhone($user);
            }
            if (!empty($this->utils->getUserSSN($user))) {
                $requestParams['SocialSecurityNumber'] = $this->utils->getUserSSN($user);
            }
            unset($requestParams['EtiId']);
            unset($requestParams['userData']);

            $response = $this->plugin->callComputopService($requestParams, $payment, 'AfterpayInvoiceRecurring', $payment->getCTPaymentURL());

            if ($response->getStatus() !== CTEnumStatus::OK) {
                $data = [
                    'success' => false,
                    'message' => "Error",
                ];
            } else {
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResult($response);
                $this->updateRefNrWithComputopFromOrderNumber($orderNumber);
                $data = [
                    'success' => true,
                    'data' => [
                        'orderNumber' => $orderNumber,
                        'transactionId' => $response->getTransID(),
                    ],
                ];
            }
            echo Zend_Json::encode($data);
        }
    }
}

