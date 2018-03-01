<?php

use Fatchip\CTPayment\CTPaymentService;

class Shopware_Controllers_Backend_FatchipCTOrder extends Shopware_Controllers_Backend_ExtJs
{

    const PAYMENTSTATUSPARTIALLYPAID = 11;
    const PAYMENTSTATUSPAID = 12;
    const PAYMENTSTATUSOPEN= 17;
    const PAYMENTSTATUSRESERVED = 18;

    /**
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    private $plugin;

    private $config;

    /**
     * @var CTPaymentService
     */
    private $paymentService;

    /** @var Util $utils * */
    protected $utils;


    public function init()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        parent::init();
    }

    public function fatchipCTDebitAction()
    {
        $request = $this->Request();
        try {
            $orderId = $request->getParam('id');

            if (!$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId)) {
                $message ='Bestellung nicht gefunden';
                throw new Exception($message);
            }

            if (!$this->fcct_isOrderRefundable($order)) {
                $errorMessage = 'Gutschrift nicht möglich.';
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

            $amount = $this->getRefundAmount($order, $positionIds, $includeShipment);
            $paymentClass->setAmount($amount);
            $paymentClass->setCurrency($order->getCurrency());

            if (strpos($order->getPayment()->getName(), 'fatchip_computop_klarna_') === 0) {
                $paymentClass->setOrderDesc($this->getKlarnaOrderDesc($order, $positionIds));
            }

            $captureResponse = $paymentClass->refund($order->getAttribute()->getfatchipctPayid(), $amount, $order->getCurrency());

            if ($captureResponse->getStatus() == 'OK') {
                $this->markPositionsAsRefunded($order, $positionIds, $includeShipment);
                $response = array('success' => true);
            } else {
                $errorMessage = 'Gutschrift (zur Zeit) nicht möglich: ' . $captureResponse->getDescription();
                $response = array('success' => false, 'error_message' => $errorMessage);
            }
        } catch (Exception $e) {
            $response = array('success' => false, 'error_message' => $e->getMessage());
        }

        $this->View()->assign($response);


    }

    public function fatchipCTTGetButtonStateAction() {

        $request = $this->Request();
        try {
            $orderId = $request->getParam('id');
            if (!$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId)) {
                $message ='Bestellung nicht gefunden';
                throw new Exception($message);
            }

            $response = array('success' => true, 'isOrderRefundable' => $this->fcct_isOrderRefundable($order), 'isOrderCapturable' => $this->fcct_isOrderCapturable($order));
            $this->View()->assign($response);
        } catch (Exception $e) {
            $response = array('success' => false, 'error_message' => $e->getMessage());
        }


    }

    public function fatchipCTCaptureOrderAction()
    {
        $request = $this->Request();
        try {
            $orderId = $request->getParam('id');

            if (!$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId)) {
                $message ='Bestellung nicht gefunden';
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
            $paymentClass->setAmount($amount);
            $paymentClass->setCurrency($order->getCurrency());
            $captureResponse = $paymentClass->capture($order->getAttribute()->getfatchipctPayid(), $amount, $order->getCurrency());

            if ($captureResponse->getStatus() == 'OK') {
                $this->markPositionsAsCaptured($order, $positionIds, $includeShipment);
                $this->inquireAndupdatePaymentStatus($order,$paymentClass);
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

    private function fcct_isOrderCapturable($order) {

         if (!$this->orderHasComputopPayment($order)) {
             return false;
         }

        //Not all paymentclasses offer a capture URL. If not, the order is not capturable
        $paymentClass = $this->getCTPaymentClassForOrder($order);
        $captureURL =$paymentClass->getCTCaptureURL();
        if (empty($captureURL)) {
            return false;
        }

         return true;
    }

    private function fcct_isOrderRefundable($order) {
        if (!$this->orderHasComputopPayment($order)) {
            return false;
        }

        //Not all paymentclasses offer a refund URL. If not, the order is not refundable
        $paymentClass = $this->getCTPaymentClassForOrder($order);
        $refundURL =$paymentClass->getCTRefundURL();

        return !empty($refundURL);
    }

    private function createCTOrderFromSWorder($swOrder) {
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

        foreach ($order->getDetails() as $position) {
            if (!in_array($position->getId(), $positionIds)) {
                continue;
            }

            $positionAttribute = $position->getAttribute();

            $alreadyCapturedAmount = $positionAttribute ? $positionAttribute->getfatchipctCaptured() : 0;
            //add difference between total price and already captured amount
            $positionPrice = round($position->getPrice(), 2);

            $amount += ($positionPrice * $position->getQuantity()) - $alreadyCapturedAmount;

            if ($position->getArticleNumber() == 'SHIPPING') {
                $includeShipment = false;
            }
        }

        if ($includeShipment) {
              $amount += $order->getInvoiceShipping();
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

        foreach ($order->getDetails() as $position) {
            if (!in_array($position->getId(), $positionIds)) {
                continue;
            }

            $positionAttribute = $position->getAttribute();
            $alreadyRefundedAmount = $positionAttribute ? $positionAttribute->getfatchipctDebit() : 0;
            //add difference between total price and already captured amount
            $positionPrice = round($position->getPrice(), 2);

            $amount += ($positionPrice * $position->getQuantity()) - $alreadyRefundedAmount;

            if ($position->getArticleNumber() == 'SHIPPING') {
                $includeShipment = false;
            }
        }

        if ($includeShipment) {
            $amount += $order->getInvoiceShipping();
        }

        /*Important: multiply by 100*/
        $amount = round($amount * 100, 2);

        return $amount;
    }

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

    private function orderHasComputopPayment($order) {
        if (strpos($order->getPayment()->getName(), 'fatchip_computop') !== 0) {
            return false;
        }

        return true;
    }

    private function getCTPaymentClassNameForOrder($order) {
        $name = $order->getPayment()->getName();
        /** @var CTPaymentService $service */
        $service = new CTPaymentService(null);
        $paymentMethods = $service->getPaymentMethods();

        if (array_search($name, array_column($paymentMethods, 'name')) !== false) {
            $key = array_search($name, array_column($paymentMethods, 'name'));
            return $paymentMethods[$key]['className'];
        }

        return $name;

    }

    private function getCTPaymentClassForOrder($order) {
        $ctOrder = $this->createCTOrderFromSWorder($order);
        $ctOrder->setOrderDesc('');
        $router = $this->Front()->Router();
        return $this->paymentService->getPaymentClass(
          $this->getCTPaymentClassNameForOrder($order),
          $this->config,
          $ctOrder,
          $router->assemble(['action' => 'success', 'forceSecure' => true]),
          $router->assemble(['action' => 'failure', 'forceSecure' => true]),
          $router->assemble(['action' => 'notify', 'forceSecure' => true]),
          Shopware()->Config()->shopName,
          ''
        );
    }

    private function inquireAndupdatePaymentStatus($order, $paymentClass) {

        $currentPaymentStatus = $order->getPaymentStatus()->getId();

        //Only when the current payment status = reserved or partly paid, we update the payment status
        if ($currentPaymentStatus == self::PAYMENTSTATUSRESERVED || $currentPaymentStatus == self::PAYMENTSTATUSPARTIALLYPAID) {
            $payID = $order->getAttribute()->getfatchipctPayid();
            $inquireResponse = $paymentClass->inquire($payID);

            if ($inquireResponse->getStatus() == 'OK') {
                if ($inquireResponse->getAmountAuth() == $inquireResponse->getAmountCap()) {
                    //Fully paid
                    $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', self::PAYMENTSTATUSPAID);
                    $order->setPaymentStatus($paymentStatus);
                    $this->get('models')->flush($order);
                } else if ($inquireResponse->getAmountCap() > 0){
                    //partially paid
                    $paymentStatus = $this->get('models')->find('Shopware\Models\Order\Status', self::PAYMENTSTATUSPARTIALLYPAID);
                    $order->setPaymentStatus($paymentStatus);
                    $this->get('models')->flush($order);
                }
            }
        }
    }

    private function getKlarnaOrderDesc($order, $positionIds) {
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
}
