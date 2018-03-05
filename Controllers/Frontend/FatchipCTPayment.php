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

use Shopware\Plugins\FatchipCTPayment\Util;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Shopware\Components\CSRFWhitelistAware;

require_once 'FatchipCTPayment.php';

abstract class Shopware_Controllers_Frontend_FatchipCTPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{

    const PAYMENTSTATUSPARTIALLYPAID = 11;
    const PAYMENTSTATUSPAID = 12;
    const PAYMENTSTATUSOPEN = 17;
    const PAYMENTSTATUSRESERVED = 18;

    const ERRORMSG = 'Es ist ein Fehler aufgetreten. Bitte wählen Sie eine andere Zahlungsart oder versuchen Sie es später noch einmal.<br>';

    /** @var \Fatchip\CTPayment\CTPaymentService $service */
    protected $paymentService;

    public $paymentClass = '';

    /**
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    protected $config;

    /** @var Util $utils * */
    protected $utils;

    protected $session;

    protected $router;

    /**
     * init payment controller
     */
    public function init()
    {
        // ToDo handle possible Exception
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        $this->session= Shopware()->Session();
        $this->router = $this->Front()->Router();
    }

    public function indexAction()
    {
        $this->forward('gateway');
    }

    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['notify'];
    }

    /**
     * @throws \Exception
     */
    public function gatewayAction()
    {
        $orderVars = $this->session->sOrderVariables;
        $userData = $orderVars['sUserData'];

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($this->getAmount() * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($userData['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($userData['shippingaddress']));
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);
        // Mandatory for paypalStandard
        $ctOrder->setOrderDesc($this->getOrderDesc());

        $payment = $this->paymentService->getIframePaymentClass(
            $this->paymentClass,
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'success', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserData()
        );

        $params = $payment->getRedirectUrlParams();
        $this->session->offsetSet('fatchipCTRedirectParams', $params);
        $this->redirect($payment->getHTTPGetURL($params));
    }


    /**
     * @return void
     * Cancel action method
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $ctError = [];

        $response = $this->paymentService->getDecryptedResponse($requestParams);

        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipCTRedirectParams'), $this->paymentClass, 'REDIRECT', $response);

        $ctError['CTErrorMessage'] = self::ERRORMSG . $response->getDescription();
        $ctError['CTErrorCode'] = $response->getCode();

        // remove easycredit session var
        $this->session->offsetUnset('fatchipComputopEasyCreditPayId');

        return $this->forward('shippingPayment', 'checkout', null, $this->hideError($response->getCode()) ? null : array('CTError' => $ctError));
    }

    /**
     * Cancel action method
     * @return void
     */
    public function successAction()
    {
        $requestParams = $this->Request()->getParams();

        /** @var \Fatchip\CTPayment\CTResponse $response */
        $response = $this->paymentService->getDecryptedResponse($requestParams);

        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipCTRedirectParams'), $this->paymentClass, 'REDIRECT', $response);

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getXID(),
                    self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResult($response);
                $this->handleDelayedCapture($orderNumber);
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * notify action method
     * @return void
     * @throws Exception
     */
    public function notifyAction()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $requestParams = $this->Request()->getParams();
        /** @var \Fatchip\CTPayment\CTResponse $response */
        $response = $this->paymentService->getDecryptedResponse($requestParams);

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $transactionId = $response->getTransID();
                if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $response->getTransID()])) {
                    $this->setOrderPaymentStatus($order, self::PAYMENTSTATUSPAID);
                    $this->markOrderDetailsAsFullyCaptured($order);
                }
                // else do nothing notify got here before success
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * try to load order via transaction id
     *
     * @param string $transactionId
     * @return order
     */
    protected function loadOrderByTransactionId($transactionId)
    {
        $sql = '
            SELECT id, ordernumber, paymentID, temporaryID, transactionID  FROM s_order
            WHERE transactionID=?';

        $order = Shopware()->Db()->fetchRow($sql, [$transactionId]);

        return $order;
    }

    /***
     * @return mixed
     *
     * The order description as sent to Computop.
     * Default it contains the shopname. If a paymentmethod needs a different Orderdescription, override this function.
     *
     */
    public function getOrderDesc()
    {
        return Shopware()->Config()->shopName;
    }

    public function getUserData()
    {
        // return empty string for now
        return '';
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 -
    // 5.2 -
    // 5.3 - check
    public function saveTransactionResult($response)
    {
        $transactionId = $response->getTransID();
        if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $transactionId])) {
            if ($attribute = $order->getAttribute()) {
                $attribute->setfatchipctStatus($response->getStatus());
                $attribute->setfatchipctTransid($response->getTransID());
                $attribute->setfatchipctPayid($response->getPayID());
                $attribute->setfatchipctXid($response->getXID());

                Shopware()->Models()->persist($attribute);
                Shopware()->Models()->flush();
            }
        }
    }

    private function markOrderDetailsAsFullyCaptured($order)
    {

        //mark all orderDetails as fully captured
        foreach ($order->getDetails() as $position) {

            $positionAttribute = $position->getAttribute();
            $positionAttribute->setfatchipctCaptured($position->getPrice() * $position->getQuantity());
            Shopware()->Models()->persist($positionAttribute);
        }
        Shopware()->Models()->flush();

        //and mark shipping as captured
        $orderAttribute = $order->getAttribute();
        $orderAttribute->setfatchipctShipcaptured($order->getInvoiceShipping());
        Shopware()->Models()->persist($orderAttribute);
        Shopware()->Models()->flush();
    }

    private function setOrderPaymentStatus($order, $statusID)
    {
        $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', $statusID);
        $order->setPaymentStatus($paymentStatus);
        $this->get('models')->flush($order);
    }

    /***
     * @param $orderNumber
     *
     * For computop credit card and paydirekt it is possible to set Capture to delayed in the plugin settings.
     * With delayed Captures, no Notify is sent to the shop. But because the capture is guaranteed to happen
     * we mark the order as fully paid
     */
    private function handleDelayedCapture($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);
        if ($order) {
            $paymentName = $order->getPayment()->getName();
            if (
                ($paymentName == 'fatchip_computop_creditcard' && $this->config['creditCardCaption'] == 'DELAYED') ||
                ($paymentName == 'fatchip_computop_paydirekt' && $this->config['payDirektCaption'] == 'DELAYED')
            ) {
                $this->setOrderPaymentStatus($order, self::PAYMENTSTATUSPAID);
                $this->markOrderDetailsAsFullyCaptured($order);
            }

        }
    }


    /***
     * @param $errorCode
     * @return bool
     *
     * Error code exists of numbers
     * 1st = Result
     * 2-4 = Category/Paymentmethod
     * 5-8 = Details
     */
    private function hideError($errorCode) {
        if (strlen($errorCode) > 4) {
            switch (substr($errorCode, -4)) {
                case '0053': //Cancel by User
                    return true;
                case '0703': //User Canceled
                    return true;
            }
        }

        return false;
    }
}
