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

use Shopware\Plugins\FatchipCTPayment\Util;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Abstract base class for Payment Controllers
 *
 * Class Shopware_Controllers_Frontend_FatchipCTPayment
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */
abstract class Shopware_Controllers_Frontend_FatchipCTPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{

    const PAYMENTSTATUSPARTIALLYPAID = 11;
    const PAYMENTSTATUSPAID = 12;
    const PAYMENTSTATUSOPEN = 17;
    const PAYMENTSTATUSRESERVED = 18;

    const ERRORMSG = 'Es ist ein Fehler aufgetreten. Bitte wählen Sie eine andere Zahlungsart oder versuchen Sie es später noch einmal.<br>';

    /**
     * Fatchip PaymentService
     *
     * @var \Fatchip\CTPayment\CTPaymentService $service
     */
    protected $paymentService;

    /**
     * FatchipCTpayment Plugin Bootstrap Class
     *
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    /**
     * FatchipCTPayment plugin settings
     *
     * @var array
     */
    protected $config;

    /**
     * FatchipCTPaymentUtils
     *
     * @var Util $utils *
     */
    protected $utils;

    /**
     * Shopware Session
     *
     * @var Enlight_Components_Session_Namespace
     */
    protected $session;

    /**
     *  Shopware router instance
     *
     * @var Enlight_Controller_Router
     */
    protected $router;

    /**
     * PaymentClass, needed for instantiating payment objects of the correct type
     *
     * @var string
     */
    public $paymentClass = '';

    /**
     * Init payment controller
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        $this->session = Shopware()->Session();
        $this->router = $this->Front()->Router();
    }

    /**
     * Index action method
     *
     * @return void
     */
    public function indexAction()
    {
        $this->forward('gateway');
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getWhitelistedCSRFActions()
    {
        return ['notify'];
    }

    /**
     * Gateway action method
     *
     * Creates paymentclass and redirects to Computop URL
     * Should be overridden in subclasses that need different behaviour
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $payment = $this->getPaymentClassForGatewayAction();
        $params = $payment->getRedirectUrlParams();
        $this->session->offsetSet('fatchipCTRedirectParams', $params);
        $this->redirect($payment->getHTTPGetURL($params));
    }

    /**
     * Cancel action method
     *
     * If an error occurs in the Computop call, and FailureURL is set, user is redirected to this
     * Reads error message from Response and redirects to shippingPayment page
     *
     * @return void
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipCTRedirectParams'), $this->paymentClass, 'REDIRECT', $response);

        $ctError = [];
        $ctError['CTErrorMessage'] = self::ERRORMSG; // . $response->getDescription();
        $ctError['CTErrorCode'] = $response->getCode();
        $this->session->offsetUnset('fatchipComputopEasyCreditPayId');

        $this->forward('shippingPayment', 'checkout', null, $this->hideError($response->getCode()) ? null : ['CTError' => $ctError]);
    }

    /**
     * Success action method.
     *
     * Called after Computop redirects to SuccessURL
     * If everything is OK, order is created with status Reserved, TransactionIDs are saved,
     * RefNr is updated and user is redirected to finish page
     *
     * @return void
     */
    public function successAction()
    {
        $requestParams = $this->Request()->getParams();
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

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $result = $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);

                if(!is_null($result) && $result->getStatus() == 'OK') {

                    if ($this->paymentClass == 'Paydirekt' && $this->config["payDirektCaption"] == 'AUTO') {
                        $this->handleManualCapture($customOrdernumber);
                    } elseif (strpos($this->paymentClass, 'Lastschrift') === 0  && $this->config["lastschriftCaption"] == 'AUTO') {
                        $this->handleManualCapture($customOrdernumber);
                    } elseif (strpos($this->paymentClass, 'Paypal') === 0  && $this->config["paypalCaption"] == 'AUTO') {
                        $this->handleManualCapture($customOrdernumber);
                    }
                }

                $this->forward('finish', 'checkout', null, ['sUniqueID' => $response->getPayID()]);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Notify action method
     *
     * Called if Computop sends notifications to NotifyURL,
     * used to update payment status info
     *
     * @return void
     * @throws Exception
     */
    public function notifyAction()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $requestParams = $this->Request()->getParams();
        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $this->plugin->logRedirectParams(null, $this->paymentClass, 'NOTIFY', $response);

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
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
     * Helper function that creates a payment object
     *
     * @return \Fatchip\CTPayment\CTPaymentMethodIframe
     * @throws Exception
     */
    protected function getPaymentClassForGatewayAction()
    {
        $ctOrder = $this->createCTOrder();
        $payment = $this->paymentService->getIframePaymentClass(
            $this->paymentClass,
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'success', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserDataParam()
        );
        return $payment;
    }

    /**
     * Helper funciton to create a CTOrder object for the current order
     *
     * @return CTOrder|void
     * @throws Exception
     */
    protected function createCTOrder()
    {
        $userData = $this->getUserData();

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
            return $this->forward('shippingPayment', 'checkout', null, ['CTError' => $ctError]);
        }
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);
        // Mandatory for paypalStandard
        $ctOrder->setOrderDesc($this->getOrderDesc());
        return $ctOrder;
    }

    /**
     * Gets userData array from Session
     *
     * Should be overridden in subclasses userData is needed before the order exists
     *
     * @return array
     */
    protected function getUserData()
    {
        $orderVars = $this->session->sOrderVariables;
        return $orderVars['sUserData'];
    }

    /**
     * Loads order by transactionId
     *
     * TODO Refactor with SW Model
     *
     * @param string $transactionId Order TransactionId
     *
     * @return \Shopware\Models\Order\Order
     */
    protected function loadOrderByTransactionId($transactionId)
    {
        $sql = '
            SELECT id, ordernumber, paymentID, temporaryID, transactionID  FROM s_order
            WHERE transactionID=?';

        $order = Shopware()->Db()->fetchRow($sql, [$transactionId]);

        return $order;
    }

    /**
     * Order description sent to Computop.
     *
     * Returns shopname.
     * If a paymentmethod needs a different Orderdescription, override this method.
     *
     * @return string
     */
    public function getOrderDesc()
    {
        $shopContext = $this->get('shopware_storefront.context_service')->getShopContext();
        $shopName = $shopContext->getShop()->getName();
        return $shopName;
    }

    /**
     * Sets the userData paramater for Computop calls to Shopware Version and Module Version
     *
     * @return string
     * @throws Exception
     */
    public function getUserDataParam()
    {
        $info = 'Shopware Version: %s Modul Version: %s';
        if (Util::isShopwareVersionGreaterThanOrEqual('5.4')) {
            //since shopware 5.4.0 DIC parameter was introduced
            return sprintf(
                $info,
                $this->container->getParameter('shopware.release.version'),
                $this->plugin->getVersion()
            );
        }

        return sprintf($info, \Shopware::VERSION, $this->plugin->getVersion());
    }

    /**
     * Saves the TransationIds in the Order attributes.
     *
     * @param \Fatchip\CTPayment\CTResponse $response Computop Api response
     *
     * @return void
     * @throws Exception
     */
    public function saveTransactionResult($response)
    {
        $transactionId = $response->getTransID();
        if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $transactionId])) {
            if ($attribute = $order->getAttribute()) {
                $attribute->setfatchipctStatus($response->getStatus());
                $attribute->setfatchipctTransid($response->getTransID());
                $attribute->setfatchipctPayid($response->getPayID());
                $attribute->setfatchipctXid($response->getXID());
                $attribute->setfatchipctkreditkartepseudonummer($response->getPCNr());
                $attribute->setfatchipctkreditkartebrand($response->getCCBrand());
                $attribute->setfatchipctkreditkarteexpiry($response->getCCExpiry());
                $attribute->setfatchipctPaypalbillingagreementid($response->getBillingAgreementiD());
                $attribute->setfatchipctlastschriftmandateid($response->getMandateid());
                $attribute->setfatchipctlastschriftdos($response->getDtofsgntr());
                Shopware()->Models()->persist($attribute);
                Shopware()->Models()->flush();
            }
        }
    }

    /**
     * Marks all OrderDetails and Shipping as Fully Captured
     *
     * @param Shopware\Models\Order\Order $order shopware order object
     *
     * @return void
     * @throws Exception
     */
    private function markOrderDetailsAsFullyCaptured($order)
    {
        foreach ($order->getDetails() as $position) {
            $positionAttribute = $position->getAttribute();
            $positionAttribute->setfatchipctCaptured($position->getPrice() * $position->getQuantity());
            Shopware()->Models()->persist($positionAttribute);
        }
        Shopware()->Models()->flush();

        $orderAttribute = $order->getAttribute();
        $orderAttribute->setfatchipctShipcaptured($order->getInvoiceShipping());
        Shopware()->Models()->persist($orderAttribute);
        Shopware()->Models()->flush();
    }

    /**
     * Updates Paymentstatus for an order
     *
     * @param Shopware\Models\Order\Order $order    shopware order object
     * @param integer                     $statusID shopware  paymentStatus id
     *
     * @return void
     */
    private function setOrderPaymentStatus($order, $statusID)
    {
        $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', $statusID);
        $order->setPaymentStatus($paymentStatus);
        $this->get('models')->flush($order);
    }

    protected function handleManualCapture($orderNumber) {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);
        if ($order) {

            $paymentName = $order->getPayment()->getName();

            if (($paymentName == 'fatchip_computop_creditcard' && $this->config['creditCardCaption'] != 'MANUAL') ||
                ($paymentName == 'fatchip_computop_lastschrift' && $this->config['lastschriftCaption'] != 'MANUAL') ||
                ($paymentName == 'fatchip_computop_paypal_standard' && $this->config['paypalCaption'] != 'MANUAL') ||
                ($paymentName == 'fatchip_computop_paypal_express' && $this->config['paypalCaption'] != 'MANUAL') ||
                ($paymentName == 'fatchip_computop_paydirekt' && $this->config['payDirektCaption'] != 'MANUAL')) {
                $this->captureOrder($order);

                //TODO: CAPTURE - wait for response
                $this->setOrderPaymentStatus($order, self::PAYMENTSTATUSPAID);
                $this->markOrderDetailsAsFullyCaptured($order);
            }
        }
    }

    protected function captureOrder($order) {
        /**
         * @var \Shopware\Models\Order\Order $order
         */
        $paymentClass = $this->paymentClass;

        $ctOrder = $this->createCTOrderFromSWorder($order);

        if ($paymentClass !== 'PaypalExpress'
            && $paymentClass !== 'AmazonPay'
        ) {
            $payment = $this->paymentService->getIframePaymentClass($paymentClass, $this->config, $ctOrder);
        } else {
            $payment = $this->paymentService->getPaymentClass($paymentClass, $this->config, $ctOrder);
        }

        $requestParams = $payment->getCaptureParams(
            $order->getAttribute()->getfatchipctPayid(),
            round($order->getInvoiceAmount() * 100, 2),
            $order->getCurrency(),
            $order->getAttribute()->getfatchipctTransid(),
            $order->getAttribute()->getfatchipctXid(),
            //TODO: klarna needs description
            'none'
        );

        $captureResponse = $this->plugin->callComputopService($requestParams, $payment, 'CAPTURE', $payment->getCTCaptureURL());
    }

    /**
     * For computop credit card and paydirekt it is possible
     * to set capture to delayed in the plugin settings.
     * With delayed captures no notify is sent to the shop!
     * Computop guarantess the capture so
     * we mark the order as fully paid
     *
     * @param string $orderNumber shopware order-number
     *
     * @return void
     * @throws Exception
     */
    protected function handleDelayedCapture($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);
        if ($order) {

            $paymentName = $order->getPayment()->getName();
            if (($paymentName == 'fatchip_computop_creditcard' && $this->config['creditCardCaption'] == 'DELAYED')
                || ($paymentName == 'fatchip_computop_paydirekt' && $this->config['payDirektCaption'] == 'DELAYED')
                || ($paymentName == 'fatchip_computop_lastschrift' && $this->config['lastschriftCaption'] == 'DELAYED')
            ) {
                $this->setOrderPaymentStatus($order, self::PAYMENTSTATUSPAID);
                $this->markOrderDetailsAsFullyCaptured($order);
            }
        }
    }

    /**
     * Update ordernumber with custom prefix and custom suffix
     *
     * @param string $orderNumber shopware orderNumber
     *
     * @return string new shopware ordnernumber
     */
    protected function customizeOrdernumber($orderNumber)
    {
        if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber])) {
            $payID = $order->getAttribute()->getfatchipctPayid();
            $transID = $order->getAttribute()->getfatchipctTransid();
            $xID = $order->getAttribute()->getfatchipctPayid();
            $orderPrefix = $this->config['prefixOrdernumber'];
            $orderSuffix = $this->config['suffixOrdernumber'];
            $newOrdernumber = $orderPrefix.$orderNumber.$orderSuffix;

            // replace placeholders
            $newOrdernumber = str_replace('%transid%', $transID, $newOrdernumber);
            $newOrdernumber = str_replace('%payid%', $payID, $newOrdernumber);
            $newOrdernumber = str_replace('%xid%', $xID, $newOrdernumber);

            $order->setNumber($newOrdernumber);
            Shopware()->Models()->flush($order);

            // update ordernumber in Session
            $this->session['sOrderVariables']->sOrderNumber = $newOrdernumber;

            // update order details with new ordernumber
            $sql = 'UPDATE  `s_order_details` SET ordernumber = ? WHERE ordernumber = ?';
            Shopware()->Db()->query($sql, [$newOrdernumber, $orderNumber]);

        }
        return isset($newOrdernumber) ? $newOrdernumber : $orderNumber;
    }

    /**
     * Update RefNr for Computop analytics
     *
     * @param string $orderNumber shopware orderNumber
     *
     * @return void
     */
    protected function updateRefNrWithComputopFromOrderNumber($orderNumber)
    {
        if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber])) {
            return $this->updateRefNrWithComputop($order, $this->paymentClass);
        }
    }

    /**
     * The RefNr for Computop has to be equal to the ordernumber.
     * Because the ordernumber is only known after successful payments
     * and successful saveOrder() call update the RefNr AFTER order creation
     *
     * @param \Shopware\Models\Order\Order $order        shopware order
     * @param string                       $paymentClass name of the payment class
     *
     * @return void
     */
    private function updateRefNrWithComputop($order, $paymentClass)
    {
        if ($order) {
            $ctOrder = $this->createCTOrderFromSWorder($order);
            if ($paymentClass !== 'PaypalExpress'
                && $paymentClass !== 'AmazonPay'
            ) {
                $payment = $this->paymentService->getIframePaymentClass($paymentClass, $this->config, $ctOrder);
            } else {
                $payment = $this->paymentService->getPaymentClass($paymentClass, $this->config, $ctOrder);
            }
            $payID = $order->getAttribute()->getfatchipctPayid();
            $RefNrChangeParams = $payment->getRefNrChangeParams($payID, $order->getNumber());
            $RefNrChangeParams['EtId'] = $this->getUserDataParam();
            // response is ignored
            $response = $this->plugin->callComputopService($RefNrChangeParams, $payment, 'REFNRCHANGE', $payment->getCTRefNrChangeURL());

            return $response;
        }
    }

    /**
     * Checks if error with $errorCode should be hidden from user
     *
     * ErrorCodes number positions:
     * 1   = Result
     * 2-4 = Category/Paymentmethod
     * 5-8 = Details
     *
     * @param integer $errorCode Computop errorcode
     *
     * @return bool
     */
    protected function hideError($errorCode)
    {
        if (strlen($errorCode) > 4) {
            switch (substr($errorCode, -4)) {
                case '0053': //Cancel by User
                    return true;
                case '0703': //User Canceled
                    return true;
                case '0321': //no action taken
                    return true;
            }
        }
        return false;
    }

    /**
     * Calls Inquire.aspx at Computop, but only if Order has status 'Reserved'
     *
     * If the order is Fully or Partly captured, the Order PaymentStatus is updated accordingly
     * If nothing has been captured, an error is thrown, so Computop will try again
     *
     * @param \Shopware\Models\Order\Order $order        shopware order
     * @param string                       $paymentClass paymentclass name
     *
     * @return void
     * @throws Exception
     */
    private function inquireAndupdatePaymentStatus($order, $paymentClass)
    {
        $currentPaymentStatus = $order->getPaymentStatus()->getId();

        if ($currentPaymentStatus == self::PAYMENTSTATUSRESERVED || $currentPaymentStatus == self::PAYMENTSTATUSPARTIALLYPAID) {
            $payID = $order->getAttribute()->getfatchipctPayid();
            $ctOrder = $this->createCTOrderFromSWorder($order);
            if ($paymentClass !== 'PaypalExpress'
                && $paymentClass !== 'AmazonPay'
            ) {
                $payment = $this->paymentService->getIframePaymentClass($paymentClass, $this->config, $ctOrder);
            } else {
                $payment = $this->paymentService->getPaymentClass($paymentClass, $this->config, $ctOrder);
            }

            $inquireParams = $payment->getInquireParams($payID);
            $inquireResponse = $this->plugin->callComputopService($inquireParams, $payment, 'INQUIRE', $payment->getCTInquireURL());

            if ($inquireResponse->getStatus() == 'OK') {
                if ($inquireResponse->getAmountAuth() == $inquireResponse->getAmountCap()) {
                    // fully paid
                    $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', self::PAYMENTSTATUSPAID);
                    $order->setPaymentStatus($paymentStatus);
                    $this->markOrderDetailsAsFullyCaptured($order);
                    $this->get('models')->flush($order);
                } else if ($inquireResponse->getAmountCap() > 0) {
                    // partially paid
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

    /**
     * Helper function to create a CTOrder object from a Shopware order Object
     *
     * @param \Shopware\Models\Order\Order $swOrder shopware order
     *
     * @return CTOrder $ctOrder Computop order object
     */
    private function createCTOrderFromSWorder($swOrder)
    {
        $swShipping = $swOrder->getShipping();
        $ctShippingAddress = new \Fatchip\CTPayment\CTAddress\CTAddress(
            $swShipping->getSalutation(),
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
            ''
        );

        $swBilling = $swOrder->getBilling();
        $ctBillingAddress = new \Fatchip\CTPayment\CTAddress\CTAddress(
            $swBilling->getSalutation(),
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
            ''
        );

        $ctOrder = new CTOrder();
        $ctOrder->setBillingAddress($ctBillingAddress);
        $ctOrder->setShippingAddress($ctShippingAddress);
        if ($email = $swOrder->getCustomer()->getEmail()) {
            $ctOrder->setEmail($email);
        }
        return $ctOrder;
    }
}

