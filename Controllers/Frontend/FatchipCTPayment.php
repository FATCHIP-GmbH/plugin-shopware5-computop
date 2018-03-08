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
        $this->session = Shopware()->Session();
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
        // try catch in case Address Splitter retrun exceptions
        try {
            $ctOrder->setBillingAddress($this->utils->getCTAddress($userData['billingaddress']));
            $ctOrder->setShippingAddress($this->utils->getCTAddress($userData['shippingaddress']));
        } catch (Exception $e) {
            $ctError = [];
            $ctError['CTErrorMessage'] = 'Bei der Verarbeitung Ihrer Adresse ist ein Fehler aufgetreten<BR>';
            $ctError['CTErrorCode'] = $e->getMessage();
            return $this->forward('shippingPayment', 'checkout', null,  ['CTError' => $ctError]);
        }
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
                    $response->getPayID(),
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

        $this->plugin->logRedirectParams(null, $this->paymentClass, 'NOTIFY', $response);


        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $transactionId = $response->getTransID();
                if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $response->getTransID()])) {
                    $this->updateRefNrWithComputop($order, $this->paymentClass);
                    $this->inquireAndupdatePaymentStatus($order, $this->paymentClass);
                } else {
                    throw new \RuntimeException('No order available within Notify');
                }
                // else do nothing notify got here before success
                break;
            default:
                throw new \RuntimeException('No order available within Notify');
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
        return  'Shopware Version: ' .  \Shopware::VERSION . ', Modul Version: ' . $this->plugin->getVersion() ;;
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

    /**
     * @param $order
     * @param $paymentClass
     *
     * The RefNr for Computop has to be equal to the ordernumber. As we do not have an ordernumber on the initial call,
     * we update the RefNr after order creation
     */
    private function updateRefNrWithComputop($order, $paymentClass) {
        if ($order) {
            $ctOrder = $this->createCTOrderFromSWorder($order);
            if ($paymentClass !== 'PaypalExpress' && $paymentClass !== 'AmazonPay') {
                $payment = $this->paymentService->getIframePaymentClass($paymentClass, $this->config, $ctOrder);
            } else {
                $payment = $this->paymentService->getPaymentClass($paymentClass, $this->config, $ctOrder);
            }
            $payID = $order->getAttribute()->getfatchipctPayid();
            $RefNrChangeParams = $payment->getRefNrChangeParams($payID, $order->getNumber());
            $response = $this->plugin->callComputopService($RefNrChangeParams, $payment, 'REFNRCHANGE', $payment->getCTRefNrChangeURL());
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
    private function hideError($errorCode)
    {
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

    private function inquireAndupdatePaymentStatus($order, $paymentClass)
    {

        $currentPaymentStatus = $order->getPaymentStatus()->getId();

        //Only when the current payment status = reserved or partly paid, we update the payment status
        if ($currentPaymentStatus == self::PAYMENTSTATUSRESERVED || $currentPaymentStatus == self::PAYMENTSTATUSPARTIALLYPAID) {
            $payID = $order->getAttribute()->getfatchipctPayid();
            $ctOrder = $this->createCTOrderFromSWorder($order);
            if ($paymentClass !== 'PaypalExpress' && $paymentClass !== 'AmazonPay') {
                $payment = $this->paymentService->getIframePaymentClass($paymentClass, $this->config, $ctOrder);
            } else {
                $payment = $this->paymentService->getPaymentClass($paymentClass, $this->config, $ctOrder);
            }
            $inquireParams = $payment->getInquireParams($payID);


            $inquireResponse = $this->plugin->callComputopService($inquireParams, $payment, 'INQUIRE', $payment->getCTInquireURL());


            if ($inquireResponse->getStatus() == 'OK') {
                if ($inquireResponse->getAmountAuth() == $inquireResponse->getAmountCap()) {
                    //Fully paid
                    $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', self::PAYMENTSTATUSPAID);
                    $order->setPaymentStatus($paymentStatus);
                    $this->markOrderDetailsAsFullyCaptured($order);
                    $this->get('models')->flush($order);
                } else if ($inquireResponse->getAmountCap() > 0) {
                    //partially paid
                    $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', self::PAYMENTSTATUSPARTIALLYPAID);
                    $order->setPaymentStatus($paymentStatus);
                    $this->get('models')->flush($order);
                } else {
                    //if nothing has been captured, we throw an error, so Computop will send another Notification
                    //if the capture has been made either manually or delayed within 24 hours, we will get a notification and be able
                    //to mark the order as captured
                    throw new \RuntimeException('No Capture in InquireResponse within first hour');

                }
            }
        }
    }

    private function createCTOrderFromSWorder($swOrder)
    {
        $swShipping = $swOrder->getShipping();

        $ctShippingAddress = new \Fatchip\CTPayment\CTAddress\CTAddress($swShipping->getSalutation(),
            $swShipping->getCompany(),
            $swShipping->getFirstName(),
            $swShipping->getLastName(),
            $swShipping->getStreet(),
            '',
            $swShipping->getZipCode(),
            $swShipping->getCity(),
            $this->utils->getCTCountryIso($swOrder->getShipping()->getCountry()->getId()),
            $this->utils->getCTCountryIso3($swOrder->getShipping()->getCountry()->getId()),
            '',
            '');

        $swBilling = $swOrder->getBilling();

        $ctBillingAddress = new \Fatchip\CTPayment\CTAddress\CTAddress($swBilling->getSalutation(),
            $swBilling->getCompany(),
            $swBilling->getFirstName(),
            $swBilling->getLastName(),
            $swBilling->getStreet(),
            '',
            $swBilling->getZipCode(),
            $swBilling->getCity(),
            $this->utils->getCTCountryIso($swOrder->getBilling()->getCountry()->getId()),
            $this->utils->getCTCountryIso3($swOrder->getBilling()->getCountry()->getId()),
            '',
            '');


        $ctOrder = new \Fatchip\CTPayment\CTOrder\CTOrder();
        $ctOrder->setBillingAddress($ctBillingAddress);
        $ctOrder->setShippingAddress($ctShippingAddress);
        if ($email = $swOrder->getCustomer()->getEmail()) {
            $ctOrder->setEmail($email);
        }
        return $ctOrder;
    }
}
