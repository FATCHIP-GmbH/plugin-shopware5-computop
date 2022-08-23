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
                $requestParams['IBAN'] = $this->utils->removeWhitespaces($this->utils->getUserAfterpayInstallmentIban($user));
                break;

            /* TODO
            case 'fatchip_computop_afterpay_consolidatedinvoice':
                $requestParams['PayType'] = 'ConsolidatedInvoice';
                $requestParams['InvoiceDate'] = '2018-08-16';
                break;
            */
        }

        if ($this->config['debuglog'] === 'extended') {
            $sessionID = $this->session->get('sessionId');
            $basket = var_export($this->session->offsetGet('sOrderVariables')->getArrayCopy(), true);
            $customerId = $this->session->offsetGet('sUserId');
            $paymentName = $this->paymentClass;
            $this->utils->log('Redirecting to ' . $payment->getRedirectUrlParams(), ['payment' => $paymentName, 'UserID' => $customerId, 'basket' => $basket, 'SessionID' => $sessionID]);
        }

        $response = $this->plugin->callComputopService($requestParams, $payment, 'AFTERPAY', $payment->getCTPaymentURL());

        if ($this->config['debuglog'] === 'extended') {
            $sessionID = $this->session->get('sessionId');
            if (!is_null($this->session->offsetGet('sOrderVariables'))) {
                $basket = var_export($this->session->offsetGet('sOrderVariables')->getArrayCopy(), true);
            } else {
                $basket = 'NULL';
            }
            $customerId = $this->session->offsetGet('sUserId');
            $paymentName = $this->paymentClass;
            $this->utils->log('SuccessAction: ' , ['payment' => $paymentName, 'UserID' => $customerId, 'basket' => $basket, 'SessionID' => $sessionID, 'Request' => $requestParams, 'Response' => $response]);
        }

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                try {
                    $orderNumber = $this->saveOrder(
                        $response->getTransID(),
                        $response->getPayID(),
                        self::PAYMENTSTATUSRESERVED
                    );
                } catch (Exception $e) {
                    $this->utils->log('SuccessAction Order could not be saved. Check if session was lost upon returning:' , ['payment' => $paymentName, 'UserID' => $customerId, 'SessionID' => $sessionID, 'response' => $response, 'error' => $e->getMessage()]);
                    $this->forward('failure');
                }
                $this->saveTransactionResult($response);

                $this->session->offsetUnSet('FatchipComputopAfterpayProductNr');

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $ctError = [];
                $ctError['CTErrorMessage'] = Shopware()->Snippets()
                    ->getNamespace('frontend/FatchipCTPayment/translations')
                    ->get('errorGeneral'); // . $response->getDescription();
                $ctError['CTErrorCode'] = ''; //$response->getCode();
                $this->session->offsetUnSet('FatchipComputopAfterpayProductNr');
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

