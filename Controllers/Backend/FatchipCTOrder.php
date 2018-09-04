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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Backend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

use Fatchip\CTPayment\CTPaymentService;

/**
 * Backend order controller
 *
 * Class Shopware_Controllers_Backend_FatchipCTOrder
 */
class Shopware_Controllers_Backend_FatchipCTOrder extends Shopware_Controllers_Backend_ExtJs
{

    const PAYMENTSTATUSPARTIALLYPAID = 11;
    const PAYMENTSTATUSPAID = 12;
    const PAYMENTSTATUSOPEN = 17;
    const PAYMENTSTATUSRESERVED = 18;
    const PAYMENTSTATUSRECREDITING = 20;

    /**
     * FatchipCTpayment Plugin Bootstrap Class
     * @var \Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    private $plugin;

    /**
     * FatchipCTPayment Configuration
     * @var array
     */
    private $config;

    /**
     * Payment Service
     * @var CTPaymentService
     */
    private $paymentService;

    /**
     * Utlis
     * @var \Shopware\Plugins\FatchipCTPayment\Util $utils * */
    protected $utils;


    /**
     * Initialises backend order
     */
    public function init()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        parent::init();
    }

    /**
     * Action to make a debit for selected positions
     * If successful, positions are marked as debited. A call to inquire.aspx is made and paymentstatus updated
     *
     */
    public function fatchipCTDebitAction()
    {
        try {
            $orderId = $this->Request()->getParam('id');

            if (!$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId)) {
                $message = 'Bestellung nicht gefunden';
                throw new Exception($message);
            }

            if (!$this->fcct_isOrderRefundable($order)) {
                $errorMessage = 'Gutschrift nicht möglich.';
                throw new Exception($errorMessage);
            }

            if ($this->Request()->getParam('includeShipment') === 'true') {
                $includeShipment = true;
            } else {
                $includeShipment = false;
            }

            //positions ?
            $positionIds = $this->Request()->get('positionIds') ? json_decode($this->Request()->get('positionIds')) : array();

            $paymentClass = $this->getCTPaymentClassForOrder($order);

            $amount = $this->getRefundAmount($order, $positionIds, $includeShipment);

            $orderDesc ='none';
            if (strpos($order->getPayment()->getName(), 'fatchip_computop_klarna_') === 0) {
                $orderDesc = $this->getKlarnaOrderDesc($order, $positionIds);
            }

            $requestParams = $paymentClass->getRefundParams(
                $order->getAttribute()->getfatchipctPayid(),
                $amount,
                $order->getCurrency(),
                $order->getAttribute()->getfatchipctTransid(),
                $order->getAttribute()->getfatchipctXid(),
                $orderDesc,
                $order->getAttribute()->getfatchipctKlarnainvno()
            );



            $refundResponse = $this->plugin->callComputopService($requestParams, $paymentClass, 'REFUND', $paymentClass->getCTRefundURL());

            if ($refundResponse->getStatus() == 'OK') {
                $this->markPositionsAsRefunded($order, $positionIds, $includeShipment);
                $this->inquireAndupdatePaymentStatusAfterRefund($order, $paymentClass);
                $response = array('success' => true);
            } else {
                $errorMessage = 'Gutschrift (zur Zeit) nicht möglich: ' . $refundResponse->getDescription();
                $response = array('success' => false, 'error_message' => $errorMessage);
            }
        } catch (Exception $e) {
            $response = array('success' => false, 'error_message' => $e->getMessage());
        }

        $this->View()->assign($response);


    }

    /**
     * Action to enable/disable the capture and debit buttons depending on order status
     */
    public function fatchipCTTGetButtonStateAction()
    {

        $request = $this->Request();
        try {
            $orderId = $request->getParam('id');
            if (!$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId)) {
                $message = 'Bestellung nicht gefunden';
                throw new Exception($message);
            }

            $response = array('success' => true, 'isOrderRefundable' => $this->fcct_isOrderRefundable($order), 'isOrderCapturable' => $this->fcct_isOrderCapturable($order));
            $this->View()->assign($response);
        } catch (Exception $e) {
            $response = array('success' => false, 'error_message' => $e->getMessage());
        }

    }

    /**
     * Action to make a capture for selected positions
     * If successful, positions are marked as captured. A call to inquire.aspx is made and paymentstatus updated
     *
     */
    public function fatchipCTCaptureOrderAction()
    {
        $request = $this->Request();
        try {
            $orderId = $request->getParam('id');

            if (!$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId)) {
                $message = 'Bestellung nicht gefunden';
                throw new Exception($message);
            }

            if (!$this->fcct_isOrderCapturable($order)) {
                $errorMessage = 'Capture nicht möglich.';
                throw new Exception($errorMessage);
            }

            if ($request->getParam('includeShipment') === 'true') {
                $includeShipment = true;
            } else {
                $includeShipment = false;
            }

            //positions ?
            $positionIds = $request->get('positionIds') ? json_decode($request->get('positionIds')) : array();


            $paymentClass = $this->getCTPaymentClassForOrder($order);

            $amount = $this->getCaptureAmount($order, $positionIds, $includeShipment);

            $orderDesc ='none';
            if (strpos($order->getPayment()->getName(), 'fatchip_computop_klarna_') === 0) {
                $orderDesc = $this->getKlarnaOrderDesc($order, $positionIds);
            }

            $requestParams = $paymentClass->getCaptureParams(
                $order->getAttribute()->getfatchipctPayid(),
                $amount,
                $order->getCurrency(),
                $order->getAttribute()->getfatchipctTransid(),
                $order->getAttribute()->getfatchipctXid(),
                $orderDesc
            );

            $captureResponse = $this->plugin->callComputopService($requestParams, $paymentClass, 'CAPTURE', $paymentClass->getCTCaptureURL());

            if ($captureResponse->getStatus() == 'OK') {
                $this->markPositionsAsCaptured($order, $positionIds, $includeShipment);
                $this->inquireAndupdatePaymentStatusAfterCapture($order, $paymentClass);
                $this->saveInvNo($captureResponse);
                $response = array('success' => true);
            } else {
                $errorMessage = 'Capture (zur Zeit) nicht möglich: ' . $captureResponse->getDescription();
                $response = array('success' => false, 'error_message' => $errorMessage);
            }
        } catch (Exception $e) {
            $response = array('success' => false, 'error_message' => $e->getMessage());
        }

        $this->View()->assign($response);
    }

    /**
     * Checks if capture can be made for an order. A capture is possible if:
     * 1. it is a Computop payment
     * 2. this payment method offers a capture url in the library
     *
     * @param $order
     * @return bool
     */
    private function fcct_isOrderCapturable($order)
    {

        if (!$this->orderHasComputopPayment($order)) {
            return false;
        }

        //Not all paymentclasses offer a capture URL. If not, the order is not capturable
        $paymentClass = $this->getCTPaymentClassForOrder($order);
        $captureURL = $paymentClass->getCTCaptureURL();
        if (empty($captureURL)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if debit can be made for an order. A debit is possible if:
     * 1. it is a Computop payment
     * 2. this payment method offers a refund url in the library
     *
     * @param $order
     * @return bool
     */
    private function fcct_isOrderRefundable($order)
    {
        if (!$this->orderHasComputopPayment($order)) {
            return false;
        }

        //Not all paymentclasses offer a refund URL. If not, the order is not refundable
        $paymentClass = $this->getCTPaymentClassForOrder($order);
        $refundURL = $paymentClass->getCTRefundURL();

        return !empty($refundURL);
    }

    /**
     * Creates a Computop Order object from a Shopware order object.
     * @param $swOrder
     * @return \Fatchip\CTPayment\CTOrder\CTOrder
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

    /**
     * return amount to capture from positions
     *
     * @param object $order
     * @param array $positionIds
     * @param bool $includeShipment
     * @return double
     */
    protected function getCaptureAmount($order, $positionIds, $includeShipment = false)
    {
        $amount = 0;
        $isTaxFree = $order->getTaxFree();
        $isNet = $order->getNet();
        $calcBrutto = (!$isTaxFree && $isNet);;

        foreach ($order->getDetails() as $position) {
            if (!in_array($position->getId(), $positionIds)) {
                continue;
            }


            $positionAttribute = $position->getAttribute();

            $alreadyCapturedAmount = $positionAttribute ? $positionAttribute->getfatchipctCaptured() : 0;
            //add difference between total price and already captured amount
            $positionPrice = round($position->getPrice(), 2);
            $taxRate = $position->getTaxRate();

            if (!$calcBrutto) {
                $amount += ($positionPrice * $position->getQuantity());
            } else {
                $amount += (($positionPrice * $position->getQuantity()) * (1 + ($taxRate / 100)));
            }

            $amount = round($amount,2);
            $amount -=  $alreadyCapturedAmount;

            if ($position->getArticleNumber() == 'SHIPPING') {
                $includeShipment = false;
            }
        }

        if ($includeShipment) {
            if (!$calcBrutto) {
                $amount += $order->getInvoiceShipping();
            } else {
                $amount += round(($order->getInvoiceShippingNet() * (1 + ($taxRate / 100))),2);
            }
        }

        /*Important: multiply by 100*/
        $amount = round($amount * 100, 2);

        return $amount;
    }

    /**
     * return amount to capture from positions
     *
     * @param object $order
     * @param array $positionIds
     * @param bool $includeShipment
     * @return double
     */
    protected function getRefundAmount($order, $positionIds, $includeShipment = false)
    {
        $amount = 0;
        $isTaxFree = $order->getTaxFree();
        $isNet = $order->getNet();
        $calcBrutto = (!$isTaxFree && $isNet);;

        foreach ($order->getDetails() as $position) {
            if (!in_array($position->getId(), $positionIds)) {
                continue;
            }

            $positionAttribute = $position->getAttribute();
            $alreadyRefundedAmount = $positionAttribute ? $positionAttribute->getfatchipctDebit() : 0;
            //add difference between total price and already captured amount
            $positionPrice = round($position->getPrice(), 2);
            $taxRate = $position->getTaxRate();

            if (!$calcBrutto) {
                $amount += ($positionPrice * $position->getQuantity());
            } else {
                $amount += (($positionPrice * $position->getQuantity()) * (1 + ($taxRate / 100)));
            }
            $amount = round($amount,2);
            $amount -=  $alreadyRefundedAmount;

            if ($position->getArticleNumber() == 'SHIPPING') {
                $includeShipment = false;
            }
        }


        if ($includeShipment) {
            if (!$calcBrutto) {
                $amount += $order->getInvoiceShipping();
            } else {
                $amount += round(($order->getInvoiceShippingNet() * (1 + ($taxRate / 100))),2);
            }
        }

        /*Important: multiply by 100*/
        $amount = round($amount * 100, 2);

        return $amount;
    }

    /**
     * Saves the amount captured in position attributes and the amount shipping captured in the order attributes
     * @param $order
     * @param $positionIds
     * @param bool $includeShipment
     */
    private function markPositionsAsCaptured($order, $positionIds, $includeShipment = false)
    {
        foreach ($order->getDetails() as $position) {
            if (!in_array($position->getId(), $positionIds)) {
                continue;
            }

            $positionAttribute = $position->getAttribute();
            $positionAttribute->setfatchipctCaptured($position->getPrice() * $position->getQuantity());

            Shopware()->Models()->persist($positionAttribute);
            Shopware()->Models()->flush();

            //check if shipping is included as position
            if ($position->getArticleNumber() == 'SHIPPING') {
                $includeShipment = false;
            }
        }

        if ($includeShipment) {
            $orderAttribute = $order->getAttribute();
            $orderAttribute->setfatchipctShipcaptured($order->getInvoiceShipping());
            Shopware()->Models()->persist($orderAttribute);
            Shopware()->Models()->flush();
        }
    }

    /**
     * Saves the amount debited in position attributes and the amount shipping debited in the order attributes
     *
     * @param $order
     * @param $positionIds
     * @param bool $includeShipment
     */
    private function markPositionsAsRefunded($order, $positionIds, $includeShipment = false)
    {
        foreach ($order->getDetails() as $position) {
            if (!in_array($position->getId(), $positionIds)) {
                continue;
            }

            $positionAttribute = $position->getAttribute();
            $positionAttribute->setfatchipctDebit($position->getPrice() * $position->getQuantity());

            Shopware()->Models()->persist($positionAttribute);
            Shopware()->Models()->flush();

            //check if shipping is included as position
            if ($position->getArticleNumber() == 'SHIPPING') {
                $includeShipment = false;
            }
        }

        if ($includeShipment) {
            $orderAttribute = $order->getAttribute();
            $orderAttribute->setfatchipctShipdebit($order->getInvoiceShipping());
            Shopware()->Models()->persist($orderAttribute);
            Shopware()->Models()->flush();
        }
    }

    /**
     * cheks if an order was paid with a computop payment method
     *
     * @param $order
     * @return bool
     */
    private function orderHasComputopPayment($order)
    {
        if (strpos($order->getPayment()->getName(), 'fatchip_computop') !== 0) {
            return false;
        }

        return true;
    }

    /**
     * Returns the payment class name that has to be instantiated for this order
     * @param $order
     * @return mixed
     */
    private function getCTPaymentClassNameForOrder($order)
    {
        $name = $order->getPayment()->getName();
        /** @var CTPaymentService $service */
        $paymentMethods = $this->paymentService->getPaymentMethods();

        if (array_search($name, array_column($paymentMethods, 'name')) !== false) {
            $key = array_search($name, array_column($paymentMethods, 'name'));
            return $paymentMethods[$key]['className'];
        }

        return $name;

    }

    /**
     * Returns an intantiated payment class object for the current order
     * @param $order
     * @return \Fatchip\CTPayment\CTPaymentMethodsIframe\Sofort
     */
    private function getCTPaymentClassForOrder($order)
    {
        $ctOrder = $this->createCTOrderFromSWorder($order);
        $ctOrder->setOrderDesc('');
        $router = $this->Front()->Router();
        $paymentClassName = $this->getCTPaymentClassNameForOrder($order);

        if ($paymentClassName !== 'PaypalExpress' && $paymentClassName !== 'AmazonPay') {
            return $this->paymentService->getIframePaymentClass(
                $paymentClassName,
                $this->config,
                $ctOrder,
                $router->assemble(['action' => 'success', 'forceSecure' => true]),
                $router->assemble(['action' => 'failure', 'forceSecure' => true]),
                $router->assemble(['action' => 'notify', 'forceSecure' => true]),
                Shopware()->Config()->shopName,
                '-'
            );
        } else {
            return $this->paymentService->getPaymentClass(
                $paymentClassName,
                $this->config,
                $ctOrder,
                $router->assemble(['action' => 'success', 'forceSecure' => true]),
                $router->assemble(['action' => 'failure', 'forceSecure' => true]),
                $router->assemble(['action' => 'notify', 'forceSecure' => true]),
                Shopware()->Config()->shopName,
                '-'
            );
        }
    }

    /**
     * If current payment status is reserverd or partially paid, a call to inquire.aspx is made.
     * If the order is fully captured, order payment status is updated to fully paid.
     * If the order is partially captured, order payment status is updated to partially paid.
     *
     * @param $order
     * @param $paymentClass
     */
    private function inquireAndupdatePaymentStatusAfterCapture($order, $paymentClass)
    {

        $currentPaymentStatus = $order->getPaymentStatus()->getId();

        //Only when the current payment status = reserved or partly paid, we update the payment status
        if ($currentPaymentStatus == self::PAYMENTSTATUSRESERVED || $currentPaymentStatus == self::PAYMENTSTATUSPARTIALLYPAID) {
            $payID = $order->getAttribute()->getfatchipctPayid();

            $requestParams = $paymentClass->getInquireParams(
                $payID
            );

            $inquireResponse = $this->plugin->callComputopService($requestParams, $paymentClass, 'INQUIRE', $paymentClass->getCTInquireURL());


            if ($inquireResponse->getStatus() == 'OK') {
                if ($inquireResponse->getAmountAuth() == $inquireResponse->getAmountCap()) {
                    //Fully paid
                    $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', self::PAYMENTSTATUSPAID);
                    $order->setPaymentStatus($paymentStatus);
                    $this->get('models')->flush($order);
                } else if ($inquireResponse->getAmountCap() > 0) {
                    //partially paid
                    $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', self::PAYMENTSTATUSPARTIALLYPAID);
                    $order->setPaymentStatus($paymentStatus);
                    $this->get('models')->flush($order);
                }
            }
        }
    }

    /**
     * If current payment status is fully paid or partially paid, a call to inquire.aspx is made.
     * If the order is partially or fully debited, order payment status is updated to crediting
     *
     * @param $order
     * @param $paymentClass
     */
    private function inquireAndupdatePaymentStatusAfterRefund($order, $paymentClass)
    {

        $currentPaymentStatus = $order->getPaymentStatus()->getId();

        //Only when the current payment status = reserved or partly paid, we update the payment status

        if ($currentPaymentStatus == self::PAYMENTSTATUSPAID || $currentPaymentStatus == self::PAYMENTSTATUSPARTIALLYPAID) {

            $requestParams = $paymentClass->getInquireParams(
                $order->getAttribute()->getfatchipctPayid()
            );

            $inquireResponse = $this->plugin->callComputopService($requestParams, $paymentClass, 'INQUIRE', $paymentClass->getCTInquireURL());


            if ($inquireResponse->getStatus() == 'OK') {
                if ($inquireResponse->getAmountCred() >= 0) {
                    //Fully paid
                    $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', self::PAYMENTSTATUSRECREDITING);
                    $order->setPaymentStatus($paymentStatus);
                    $this->get('models')->flush($order);
                }
            }
        }
    }

    /**
     * For klarna capture or debit actions, a certain formatting of the orderdescription is needed.
     * @param $order
     * @param $positionIds
     * @return string
     */
    private function getKlarnaOrderDesc($order, $positionIds)
    {
        $orderDesc = '';
        foreach ($order->getDetails() as $position) {
            if (!in_array($position->getId(), $positionIds)) {
                continue;
            }

            if (!empty($orderDesc)) {
                $orderDesc .= ' + ';
            }
            $orderDesc .= $position->getQuantity() . ';' . $position->getArticleID() . ';' . $position->getArticlename() . ';'
                . $position->getPrice() * 100 . ';' . $position->getTaxRate() . ';0;0';


            return $orderDesc;


        }
    }

    /**
     * Saves the InvoiceNr from Computop response in order attributes for Klarna payments
     * @param $response
     */
    private function saveInvNo($response)
    {
        $transactionId = $response->getTransID();
        if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $transactionId])) {
            if ($attribute = $order->getAttribute()) {
                    if (!empty($response->getInvNo())) {
                        $attribute->setfatchipctKlarnainvno($response->getInvNo());
                        Shopware()->Models()->persist($attribute);
                        Shopware()->Models()->flush();
                }
            }
        }
    }
}
