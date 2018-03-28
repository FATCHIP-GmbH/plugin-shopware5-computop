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

/**
 * Abstract base class for Payment Controllers
 * Class Shopware_Controllers_Frontend_FatchipCTPayment
 */
abstract class Shopware_Controllers_Frontend_FatchipCTPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{

    const PAYMENTSTATUSPARTIALLYPAID = 11;
    const PAYMENTSTATUSPAID = 12;
    const PAYMENTSTATUSOPEN = 17;
    const PAYMENTSTATUSRESERVED = 18;

    const ERRORMSG = 'Es ist ein Fehler aufgetreten. Bitte wählen Sie eine andere Zahlungsart oder versuchen Sie es später noch einmal.<br>';

    /**
     * PaymentService
     * @var \Fatchip\CTPayment\CTPaymentService $service */
    protected $paymentService;

    /**
     * PaymentClass, needed for instatiating payment objects of the correct type     *
     * @var string
     */
    public $paymentClass = '';

    /**
     * FatchipCTpayment Plugin Bootstrap Class
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    /**
     * Array containing the pluginsetting
     * @var array
     */
    protected $config;

    /** @var Util $utils * */
    protected $utils;

    /**
     * contains the session
     * @var  Enlight_Components_Session_Namespace */
    protected $session;

    /**
     *  contains the router instance
     * @var  Enlight_Controller_Router */
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

    /**
     * index action method
     * @return void
     */
    public function indexAction()
    {
        $this->forward('gateway');
    }

    /**
     * Whitelist notifyAction
     * $return array
     */
    public function getWhitelistedCSRFActions()
    {
        return ['notify'];
    }

    /**
     * Gatewayaction method
     * Creates paymentclass and redirects to Computop URL
     * Should be overridden in subclasses that need different behaviour
     * @throws \Exception
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
     * If an error occurs in the Computop call, and FailureURL is set, user is redirected to this
     * Reads error message from Response and redirects to shippingPayment page
     * @return void
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

        return $this->forward('shippingPayment', 'checkout', null, $this->hideError($response->getCode()) ? null : ['CTError' => $ctError]);
    }

    /**
     * Success action method
     * Called after Computop redirects to SuccessURL
     * If everything is OK, order is created with status Reserved, TransactionIDs are saved,
     * RefNr is updated and user is redirected to finish page
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
                $this->updateRefNrWithComputopFromOrderNumber($orderNumber);
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * notify action method
     * Called if Computop sends notifications to NotifyURL, used to update payment status info
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
     * Helper function that creates a payment object
     * @return \Fatchip\CTPayment\CTPaymentMethodsIframe\
     */
    protected function getPaymentClassForGatewayAction() {
        $ctOrder = $this->createCTOrder();
        $payment = $this->paymentService->getIframePaymentClass(
          $this->paymentClass,
          $this->config,
          $ctOrder,
          $this->router->assemble(['action' => 'success', 'forceSecure' => true]),
          $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
          $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
          $this->getOrderDesc(),
          $this->getUserDataParam());

        return $payment;
    }

    /**
     * Helper funciton to create a CTOrder object for the current order
     * @return CTOrder|void
     */
    protected function createCTOrder() {

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
            return $this->forward('shippingPayment', 'checkout', null,  ['CTError' => $ctError]);
        }
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);
        // Mandatory for paypalStandard
        $ctOrder->setOrderDesc($this->getOrderDesc());
        return $ctOrder;
    }

    /**
     * gets userData array from OrderVars from Session
     * shoud be overridden in sublcasses if it is needed before an order exists
     * @return mixed
     */
    protected function getUserData() {
        $orderVars = $this->session->sOrderVariables;
        return $orderVars['sUserData'];
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

    /**
     * The order description as sent to Computop.
     * Default it contains the shopname. If a paymentmethod needs a different Orderdescription, override this function.
     *
     * @return mixed
     */
    public function getOrderDesc()
    {
        return Shopware()->Config()->shopName;
    }

    /**
     * Sets the userData paramater for Computop calls to Shopware Version and Module Version
     * @return string
     */
    public function getUserDataParam()
    {
        return  'Shopware Version: ' .  \Shopware::VERSION . ', Modul Version: ' . $this->plugin->getVersion() ;;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 -
    // 5.2 -
    // 5.3 - check
    /**
     * Saves the TransationIds in the Order attributes
     * @param $response \Fatchip\CTPayment\CTResponse
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

                Shopware()->Models()->persist($attribute);
                Shopware()->Models()->flush();
            }
        }
    }

    /**
     * marks all OrderDetails and Shipping as Fully Captured
     * @param $order
     */
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

    /**
     * Updates Paymentstatus for an order
     * @param $order
     * @param $statusID
     */
    private function setOrderPaymentStatus($order, $statusID)
    {
        $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', $statusID);
        $order->setPaymentStatus($paymentStatus);
        $this->get('models')->flush($order);
    }

    /**
     * For computop credit card and paydirekt it is possible to set Capture to delayed in the plugin settings.
     * With delayed Captures, no Notify is sent to the shop. But because the capture is guaranteed to happen
     * we mark the order as fully paid
     *
     * @param $orderNumber
     */
    protected function handleDelayedCapture($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);
        if ($order) {
            $paymentName = $order->getPayment()->getName();
            if (
                ($paymentName == 'fatchip_computop_creditcard' && $this->config['creditCardCaption'] == 'DELAYED') ||
                ($paymentName == 'fatchip_computop_paydirekt' && $this->config['payDirektCaption'] == 'DELAYED') ||
                ($paymentName == 'fatchip_computop_lastschrift' && $this->config['lastschriftCaption'] == 'DELAYED')
            ) {
                $this->setOrderPaymentStatus($order, self::PAYMENTSTATUSPAID);
                $this->markOrderDetailsAsFullyCaptured($order);
            }

        }
    }

    /**
     * Trys to update RefNr in Computop Analytics for Order with OrderNumber = $Ordernumber
     * @param $orderNumber
     */
    protected function updateRefNrWithComputopFromOrderNumber($orderNumber) {
        if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber])) {
            $this->updateRefNrWithComputop($order, $this->paymentClass);
        }
    }

    /**
     * The RefNr for Computop has to be equal to the ordernumber. As we do not have an ordernumber on the initial call,
     * we update the RefNr after order creation
     * @param $order
     * @param $paymentClass
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


    /**
     * checks if error with $errorCode should be hidden from user
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
                case '0321': //no action taken
                    return true;
            }
        }

        return false;
    }

    /**
     * Calls Inquire.aspx at Computop, but only if Order has status 'Reserved'
     * If the order is Fully or Partly captured, the Order PaymentStatus is updated accordingly
     * If nothing has been captured, an error is thrown, so Computop will try again
     * @param $order
     * @param $paymentClass
     * @throws RuntimeException
     */
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

    /**
     * Helper function to create a CTOrder object from a Shopware order Object
     * @param $swOrder
     * @return CTOrder /FatchipCTPayment/CTOrder
     */
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
