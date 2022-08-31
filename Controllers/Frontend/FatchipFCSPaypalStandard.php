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

use Fatchip\FCSPayment\CTEnums\CTEnumStatus;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSPaypalStandard
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */
class Shopware_Controllers_Frontend_FatchipFCSPaypalStandard extends Shopware_Controllers_Frontend_FatchipFCSPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'PaypalStandard';

    /**
     * Gateway action method
     *
     * Creates paymentclass and redirects to Computop URL
     * Overridden to support recurring payments init
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $payment = $this->getPaymentClassForGatewayAction();
        /** @var \Fatchip\FCSPayment\CTPaymentMethodsIframe\PaypalStandard $payment */

        if ($this->utils->isAboCommerceArticleInBasket()) {
            $payment->setRTF('I');
            $payment->setTxType('AUTH');
        }
        $params = $payment->getRedirectUrlParams();
        $this->session->offsetSet('fatchipFCSRedirectParams', $params);

        if ($this->config['debuglog'] === 'extended') {
            $sessionID = $this->session->get('sessionId');
            $basket = var_export($this->session->offsetGet('sOrderVariables')->getArrayCopy(), true);
            $customerId = $this->session->offsetGet('sUserId');
            $paymentName = $this->paymentClass;
            $this->utils->log('Redirecting to ' . $payment->getHTTPGetURL($params, $this->config['creditCardTemplate']), ['payment' => $paymentName, 'UserID' => $customerId, 'basket' => $basket, 'SessionID' => $sessionID, 'parmas' => $params]);
        }

        $this->redirect($payment->getHTTPGetURL($params));
    }


    /**
     * Recurring payment action method.
     */
    public function recurringAction()
    {
        $params = $this->Request()->getParams();
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();
        $payment = $this->getPaymentClassForGatewayAction();
        $payment->setRTF('R');
        $payment->setTxType('AUTH');
        $requestParams = $payment->getRedirectUrlParams();
        $requestParams['BillingAgreementID'] = $this->getParamPaypalBillingAgreementId($params['orderId']);
        $response = $this->plugin->callComputopService($requestParams, $payment, 'PaypalRecurring', $payment->getRecurringURL());

        if ($this->Request()->isXmlHttpRequest()) {
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

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $result = $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);
                if(!is_null($response) && $response->getStatus() == 'OK') {
                    $this->autoCapture($customOrdernumber, true);
                }
                $data = [
                    'success' => true,
                    'data' => [
                        'orderNumber' => $customOrdernumber,
                        'transactionId' => $response->getTransID(),
                    ],
                ];
            }
            echo Zend_Json::encode($data);
        }
    }

    /**
     * returns paypal billing agreementId from
     * the last order to use it to authorize
     * recurring payments
     *
     * @param string $orderNumber shopware order-number
     *
     * @return boolean | string paypal biilingAgreementId
     */
    protected function getParamPaypalBillingAgreementId($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['id' => $orderNumber]);
        $agreementId = false;
        if ($order) {
            $orderAttribute = $order->getAttribute();
            $agreementId = $orderAttribute->getfatchipfcsPaypalbillingagreementid();

        }
        return $agreementId;
    }
}

