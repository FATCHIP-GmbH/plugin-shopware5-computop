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
 * @link       https://www.firstcash.com
 */

use Fatchip\CTPayment\CTEnums\CTEnumPaymentStatus;
use Fatchip\CTPayment\CTResponse;
use Shopware\Models\Order\Order;
use Shopware\Plugins\FatchipFCSPayment\Util;
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
 * @link       https://www.firstcash.com
 */
abstract class Shopware_Controllers_Frontend_FatchipFCSPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{

    const PAYMENTSTATUSPARTIALLYPAID = CTEnumPaymentStatus::PAYMENTSTATUSPARTIALLYPAID;
    const PAYMENTSTATUSPAID = CTEnumPaymentStatus::PAYMENTSTATUSPAID;
    const PAYMENTSTATUSOPEN = CTEnumPaymentStatus::PAYMENTSTATUSOPEN;
    const PAYMENTSTATUSRESERVED = CTEnumPaymentStatus::PAYMENTSTATUSRESERVED;
    const PAYMENTSTATUSREVIEWNECESSARY = CTEnumPaymentStatus::PAYMENTSTATUSREVIEWNECESSARY;
    const ORDERSTATUSREVIEWNECESSARY  = Shopware\Models\Order\Status::ORDER_STATE_CLARIFICATION_REQUIRED;


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

    public $helper;

    /**
     * Init payment controller
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipFCSPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipFCSPaymentUtils');
        $this->session = Shopware()->Session();
        $this->router = $this->Front()->Router();

        if(!empty($this->paymentClass)) {
            $className = 'Fatchip\\CTPayment\\CTHelper\\' . $this->paymentClass;

            if(class_exists($className)) {
                $this->helper = new $className();
            }
        }
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
        $ctError['CTErrorMessage'] = Shopware()->Snippets()
            ->getNamespace('frontend/FatchipCTPayment/translations')
            ->get('errorGeneral'); // . $response->getDescription();
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
     * @throws Exception
     */
    public function successAction()
    {
        $requestParams = $this->Request()->getParams();

        if(array_key_exists('fatchipCTPaymentClass', $requestParams) && $requestParams['fatchipCTPaymentClass'])  {
            $this->paymentClass = $requestParams['fatchipCTPaymentClass'];
        }

        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipCTRedirectParams'), $this->paymentClass, 'REDIRECT', $response);
        if (is_null($response) || $response->getStatus() !== CTEnumStatus::OK) {
            $this->forward('failure');
        }

        $orderNumber = $this->saveOrder(
            $response->getTransID(),
            $response->getPayID(),
            self::PAYMENTSTATUSRESERVED
        );
        $this->saveTransactionResult($response);
        $this->handleDelayedCapture($orderNumber);

        $customOrdernumber = $this->customizeOrdernumber($orderNumber);
        $response = $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);

        if(is_null($response) || $response->getStatus() !== 'OK') {
            $this->forward('failure');
        }

        $this->autoCapture($customOrdernumber);

        if($this->paymentClass == 'AmazonPay') {
            $this->forward('finish', 'FatchipCTAmazonCheckout', null, ['sUniqueID' => $response->getPayID()]);
        }
        else {
            $this->forward('finish', 'checkout', null, ['sUniqueID' => $response->getPayID()]);
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
            case CTEnumStatus::AUTHORIZED:
            case CTEnumStatus::AUTHORIZE_REQUEST:
                if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $response->getTransID()])) {
//                    $this->updateRefNrWithComputop($order, $this->paymentClass);
                    $this->inquireAndupdatePaymentStatus($order, $this->paymentClass);
                }

                // else do nothing notify got here before success
                break;
            default:
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
     * @deprecated
     * Scope sensitive
     * Use Util->createCTOrder instead
     *
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
            $ctError['CTErrorMessage'] = Shopware()->Snippets()
                ->getNamespace('frontend/FatchipCTPayment/translations')
                ->get('errorAddress');
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
     * @return Order
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

        return sprintf($info, Util::getShopwareVersion(), $this->plugin->getVersion());
    }

    /**
     * Saves the TransationIds in the Order attributes.
     *
     * @param CTResponse $response Computop Api response
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
                $attribute->setfatchipctkreditkarteschemereferenceid($response->getSchemeReferenceID());
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

    /**
     * Updates ORderstatus for an order
     *
     * @param Shopware\Models\Order\Order $order    shopware order object
     * @param integer                     $statusID shopware  paymentStatus id
     *
     * @return void
     */
    private function setOrderStatus($order, $statusID)
    {
        $orderStatus = $this->get('models')->find('Shopware\Models\Order\Status', $statusID);
        $order->setOrderStatus($orderStatus);
        $this->get('models')->flush($order);
    }

    /**
     * @param $orderNumber
     *
     * @throws Exception
     */
    protected function autoCapture($orderNumber) {
        /** @var Order $order */
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);

        if (! $order) {
            return;
        }

        $paymentName = $order->getPayment()->getName();

        if ( ! (($paymentName === 'fatchip_firstcash_creditcard' && $this->config['creditCardCaption'] === 'AUTO')
            || ($paymentName === 'fatchip_firstcash_lastschrift' && $this->config['lastschriftCaption'] === 'AUTO')
            || ($paymentName === 'fatchip_firstcash_paypal_standard' && $this->config['paypalCaption'] === 'AUTO')
            || ($paymentName === 'fatchip_firstcash_paypal_express' && $this->config['paypalCaption'] === 'AUTO')
            || ($paymentName === 'fatchip_firstcash_paydirekt' && $this->config['payDirektCaption'] === 'AUTO'))
        ) {
            return;
        }

        $captureResponse = $this->captureOrder($order);
        $captureResponseStatus = $captureResponse->getStatus();
        if ( $captureResponseStatus === 'OK') {
            $this->setOrderPaymentStatus($order, self::PAYMENTSTATUSPAID);
            $this->markOrderDetailsAsFullyCaptured($order);
        } else {
            $this->setOrderPaymentStatus($order, self::PAYMENTSTATUSREVIEWNECESSARY);
            /** @see https://tickets.fatchip.de/view.php?id=80218 */
            if ($paymentName === 'fatchip_firstcash_paypal_standard' && $this->config['paypalSetOrderStatus'] === "An") {
                $this->setOrderStatus($order, self::ORDERSTATUSREVIEWNECESSARY);
            }
        }
    }

    /**
     * @param Order $order
     *
     * @return CTResponse
     */
    protected function captureOrder($order) {
        $paymentClass = $this->paymentClass;

        $ctOrder = $this->createCTOrderFromSWorder($order);

        if ($paymentClass !== 'PaypalExpress'
            && $paymentClass !== 'AmazonPay'
        ) {
            $payment = $this->paymentService->getIframePaymentClass($paymentClass, $this->config, $ctOrder);
        } else {
            $payment = $this->paymentService->getPaymentClass($paymentClass);
        }

        $orderAttribute = $order->getAttribute();
        $requestParams = $payment->getCaptureParams(
            $orderAttribute->getfatchipctPayid(),
            round($order->getInvoiceAmount() * 100, 2),
            $order->getCurrency(),
            $orderAttribute->getfatchipctTransid(),
            $orderAttribute->getfatchipctXid(),
            //TODO: klarna needs description
            'none',
            $orderAttribute->getfatchipctkreditkarteschemereferenceid()
        );

        return $this->plugin->callComputopService($requestParams, $payment, 'CAPTURE', $payment->getCTCaptureURL());
    }

    /**
     * For firstcash credit card and paydirekt it is possible
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
            if (($paymentName == 'fatchip_firstcash_creditcard' && $this->config['creditCardCaption'] == 'DELAYED')
                || ($paymentName == 'fatchip_firstcash_paydirekt' && $this->config['payDirektCaption'] == 'DELAYED')
                || ($paymentName == 'fatchip_firstcash_lastschrift' && $this->config['lastschriftCaption'] == 'DELAYED')
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
     * @return CTResponse
     * @throws Exception
     */
    protected function updateRefNrWithComputopFromOrderNumber($orderNumber)
    {
        $repository = Shopware()->Models()->getRepository(Order::class);
        /** @var Order $order */
        if (!$order = $repository->findOneBy(['number' => $orderNumber])) {
            return null;
        }

        return $result = $this->updateRefNrWithComputop($order, $this->paymentClass);
    }

    /**
     * The RefNr for Computop has to be equal to the ordernumber.
     * Because the ordernumber is only known after successful payments
     * and successful saveOrder() call update the RefNr AFTER order creation
     *
     * @param Order  $order        shopware order
     * @param string $paymentClass name of the payment class
     *
     * @return CTResponse
     * @throws Exception
     */
    private function updateRefNrWithComputop($order, $paymentClass)
    {
        if (!$order) {
            return null;
        }

        $ctOrder = $this->createCTOrderFromSWorder($order);
        if ($paymentClass !== 'PaypalExpress'
            && $paymentClass !== 'AmazonPay'
            && $paymentClass !== 'KlarnaPayments'
        ) {
            $payment = $this->paymentService->getIframePaymentClass($paymentClass, $this->config, $ctOrder);
        } else {
            $payment = $this->paymentService->getPaymentClass($paymentClass);
        }
        $attribute = $order->getAttribute();
        $payID = $attribute->getfatchipctPayid();
        $RefNrChangeParams = $payment->getRefNrChangeParams($payID, $order->getNumber());
        $RefNrChangeParams['EtiId'] = $this->getUserDataParam();

        return $this->plugin->callComputopService(
            $RefNrChangeParams,
            $payment,
            'REFNRCHANGE',
            $payment->getCTRefNrChangeURL()
        );
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
     * @param Order $order        shopware order
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
                $payment = $this->paymentService->getPaymentClass($paymentClass);
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
     * @param Order $swOrder shopware order
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

