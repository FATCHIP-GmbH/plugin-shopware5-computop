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
 * Class Shopware_Controllers_Frontend_FatchipFCSLastschrift
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
class Shopware_Controllers_Frontend_FatchipFCSLastschrift extends Shopware_Controllers_Frontend_FatchipFCSPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = '';

    /**
     * Sets the correct paymentclass depending on the pluginsetting lastschriftDienst
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {
        parent::init();
        switch ($this->config['lastschriftDienst']) {
            case 'DIREKT':
                $this->paymentClass = 'LastschriftDirekt';
                break;
            case 'EVO':
                $this->paymentClass = 'LastschriftEVO';
                break;
            case 'INTERCARD':
                $this->paymentClass = 'LastschriftInterCard';
                break;
        }
    }

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

        $user = $this->getUserData();

        if ($this->utils->isAboCommerceArticleInBasket()) {
            $payment->setMdtSeqType('FRST');
        }

        $payment->setAccBank($this->utils->getUserLastschriftBank($user));
        $payment->setAccOwner($this->utils->getUserLastschriftKontoinhaber($user));
        $payment->setIBAN($this->utils->getUserLastschriftIban($user));

        $requestParams = $payment->getRedirectUrlParams();
        $response = $this->plugin->callComputopService($requestParams, $payment, 'LASTSCHRIFT', $payment->getCTPaymentURL());

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResult($response);

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $result = $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);

                if(!is_null($result) && $result->getStatus() == 'OK') {
                    $this->autoCapture($customOrdernumber);
                }

                $this->forward('finish', 'checkout', null, ['sUniqueID' => $response->getPayID()]);
                break;
            default:
                $ctError = [];
                $ctError['CTErrorMessage'] = Shopware()->Snippets()
                    ->getNamespace('frontend/FatchipFCSPayment/translations')
                    ->get('errorGeneral'); // . $response->getDescription();
                $ctError['CTErrorCode'] = ''; //$response->getCode();
                $this->forward('shippingPayment', 'checkout', null, array('CTError' => $ctError));

                break;
        }
    }

    /**
     * Overridden: because we do not have order yet.
     *
     * @return array|false
     */
    protected function getUserData()
    {
        return Shopware()->Modules()->Admin()->sGetUserData();
    }

    /**
     * Recurring payment action method.
     */
    public function recurringAction()
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();
        $params = $this->Request()->getParams();

        if ($this->Request()->isXmlHttpRequest()) {

            $payment = $this->getPaymentClassForGatewayAction();
            $user = $this->getUserData();

            $payment->setMdtSeqType('RCUR');

            $payment->setAccBank($this->utils->getUserLastschriftBank($user));
            $payment->setAccOwner($this->utils->getUserLastschriftKontoinhaber($user));
            $payment->setIBAN($this->utils->getUserLastschriftIban($user));

            $requestParams = $payment->getRedirectUrlParams();
            $requestParams['MandateID'] = $this->getParamLastschriftMandateId($params['orderId']);
            $requestParams['DtOfSgntr'] = $this->getParamLastschriftDos($params['orderId']);
            $response = $this->plugin->callComputopService($requestParams, $payment, 'LASTSCHRIFTRecurring', $payment->getCTPaymentURL());

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
                $result = $this->updateRefNrWithComputopFromOrderNumber($orderNumber);

                if(!is_null($result) && $result->getStatus() == 'OK') {
                    $this->autoCapture($orderNumber);
                }

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

    /**
     * returns lastschrift mandateId from
     * the last order to use it to authorize
     * recurring payments
     *
     * @param string $orderNumber shopware order-number
     *
     * @return boolean | string lastschrift agrementId
     */
    protected function getParamLastschriftMandateId($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['id' => $orderNumber]);
        $agreementId = false;
        if ($order) {
            $orderAttribute = $order->getAttribute();
            $agreementId = $orderAttribute->getfatchipfcslastschriftmandateid();

        }
        return $agreementId;
    }

    /**
     * returns lastschrift date of signature from
     * the last order to use it to authorize
     * recurring payments
     *
     * @param string $orderNumber shopware order-number
     *
     * @return boolean | string lastschrift DoS
     */
    protected function getParamLastschriftDos($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['id' => $orderNumber]);
        $DoS = false;
        if ($order) {
            $orderAttribute = $order->getAttribute();
            $DoS = $orderAttribute->getfatchipfcslastschriftdos();

        }
        return $DoS;
    }

}


