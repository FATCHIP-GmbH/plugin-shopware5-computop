<?php

use Fatchip\CTPayment\CTPaymentService;

class Shopware_Controllers_Backend_FatchipCTOrder extends Shopware_Controllers_Backend_ExtJs
{

    const PAYMENTSTATUSPARTIALLYPAID = 11;
    const PAYMENTSTATUSPAID = 12;
    const PAYMENTSTATUSOPEN = 17;
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

    /** @var \Shopware\Plugins\FatchipCTPayment\Util $utils * */
    protected $utils;

    /* order as array */
    protected $order;

    public function init()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        // set order array!as property
        $orderId = $this->Request()->getParam('id');
        $this->order = $this->getOrderArray($orderId);
        parent::init();
    }


    public function getOrderArray($orderId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select([
            'orders',
            'details',
            'documents',
            'payment',
            'customer',
            'paymentInstances',
            'shipping',
            'billing',
            'billingCountry',
            'shippingCountry',
            'billingState',
            'shippingState',
            'shop',
            'dispatch',
            'paymentStatus',
            'orderStatus',
            'documentType',
            'billingAttribute',
            'attribute',
            'detailAttribute',
            'documentAttribute',
            'shippingAttribute',
            'paymentAttribute',
            'dispatchAttribute',
            'subShop',
            'locale',
        ]);

        $builder->from('Shopware\Models\Order\Order', 'orders');
        $builder->leftJoin('orders.details', 'details')
            ->leftJoin('orders.documents', 'documents')
            ->leftJoin('documents.type', 'documentType')
            ->leftJoin('orders.payment', 'payment')
            ->leftJoin('orders.paymentStatus', 'paymentStatus')
            ->leftJoin('orders.orderStatus', 'orderStatus')
            ->leftJoin('orders.customer', 'customer')
            ->leftJoin('orders.paymentInstances', 'paymentInstances')
            ->leftJoin('orders.billing', 'billing')
            ->leftJoin('billing.country', 'billingCountry')
            ->leftJoin('billing.state', 'billingState')
            ->leftJoin('orders.shipping', 'shipping')
            ->leftJoin('orders.shop', 'shop')
            ->leftJoin('orders.dispatch', 'dispatch')
            ->leftJoin('payment.attribute', 'paymentAttribute')
            ->leftJoin('dispatch.attribute', 'dispatchAttribute')
            ->leftJoin('billing.attribute', 'billingAttribute')
            ->leftJoin('shipping.attribute', 'shippingAttribute')
            ->leftJoin('details.attribute', 'detailAttribute')
            ->leftJoin('documents.attribute', 'documentAttribute')
            ->leftJoin('orders.attribute', 'attribute')
            ->leftJoin('orders.languageSubShop', 'subShop')
            ->leftJoin('subShop.locale', 'locale')
            ->leftJoin('shipping.country', 'shippingCountry')
            ->leftJoin('shipping.state', 'shippingState')
            ->where('orders.id = ?1')
            ->setParameter(1, $orderId);
        return $builder->getQuery()->getOneOrNullResult(Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
    }

    public function fatchipCTDebitAction()
    {
        try {

            if (empty($this->order)) {
                $message = 'Bestellung nicht gefunden';
                throw new Exception($message);
            }

            if (!$this->fcct_isOrderRefundable($this->order)) {
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

            $paymentClass = $this->getCTPaymentClassForOrder($this->order);

            $amount = $this->getRefundAmount($this->order, $positionIds, $includeShipment);

            $requestParams = $paymentClass->getCaptureParams(
                $this->order['attribute']['fatchipctPayid'],
                $amount,
                $this->order['currency']
            );

            if (strpos($this->order['payment']['name'], 'fatchip_computop_klarna_') === 0) {
                $paymentClass->setOrderDesc($this->getKlarnaOrderDesc($this->order, $positionIds));
            }

            $refundResponse = $this->plugin->callComputopService($requestParams, $paymentClass, 'Refund', $paymentClass->getCTRefundURL());

            if ($refundResponse->getStatus() == 'OK') {
                $this->markPositionsAsRefunded($this->order, $positionIds, $includeShipment);
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

    public function fatchipCTTGetButtonStateAction()
    {
        try {
            if (empty($this->order)) {
                $message = 'Bestellung nicht gefunden';
                throw new Exception($message);
            }

            $response = ['success' => true, 'isOrderRefundable' => $this->fcct_isOrderRefundable($this->order), 'isOrderCapturable' => $this->fcct_isOrderCapturable($this->order)];
            $this->View()->assign($response);
        } catch (Exception $e) {
            $response = ['success' => false, 'error_message' => $e->getMessage()];
        }
    }

    public function fatchipCTCaptureOrderAction()
    {
        try {
            if (empty($this->order)) {
                $message = 'Bestellung nicht gefunden';
                throw new Exception($message);
            }

            if (!$this->fcct_isOrderCapturable($this->order)) {
                $errorMessage = 'Capture nicht möglich.';
                throw new Exception($errorMessage);
            }

            if ($this->Request()->getParam('includeShipment') === 'true') {
                $includeShipment = true;
            } else {
                $includeShipment = false;
            }

            //positions ?
            $positionIds = $this->Request()->get('positionIds') ? json_decode($this->Request()->get('positionIds')) : [];
            $amount = $this->getCaptureAmount($this->order, $positionIds, $includeShipment);

            $paymentClass = $this->getCTPaymentClassForOrder($this->order);

            $requestParams = $paymentClass->getCaptureParams(
                $this->order['attribute']['fatchipctPayid'],
                $amount,
                $this->order['currency']
            );

            $response = $this->plugin->callComputopService($requestParams, $paymentClass, 'Capture', $paymentClass->getCTCaptureURL());


            if ($response->getStatus() == 'OK') {
                $this->markPositionsAsCaptured($this->order, $positionIds, $includeShipment);
                $this->inquireAndupdatePaymentStatus($this->order, $paymentClass);
                $response = array('success' => true);
            } else {
                $errorMessage = 'Capture (zur Zeit) nicht möglich: ' . $response->getDescription();
                $response = array('success' => false, 'error_message' => $errorMessage);
            }
        } catch (Exception $e) {
            $response = array('success' => false, 'error_message' => $e->getMessage());
        }

        $this->View()->assign($response);
    }

    /* ToDO is orderHasComputopPayment($order) neccessary? poisiton tab should not be displayed for non-computop orders */
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


    private function createCTOrderFromSWorder($swOrder)
    {

        $ctShippingAddress = $this->utils->getCTAddress($swOrder['shipping']);
        $ctBillingAddress = $this->utils->getCTAddress($swOrder['billing']);

        $ctOrder = new \Fatchip\CTPayment\CTOrder\CTOrder();
        $ctOrder->setBillingAddress($ctBillingAddress);
        $ctOrder->setShippingAddress($ctShippingAddress);
        $ctOrder->setEmail($swOrder['customer']['email']);
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

        foreach ($order['details'] as $position) {
            if (!in_array($position['id'], $positionIds)) {
                continue;
            }

            $positionAttribute = $position['attribute'];

            $alreadyCapturedAmount = $positionAttribute ? $positionAttribute['fatchipctCaptured'] : 0;
            //add difference between total price and already captured amount
            $positionPrice = round($position['price'], 2);

            $amount += ($positionPrice * $position['quantity']) - $alreadyCapturedAmount;

            if ($position['articleNumber'] == 'SHIPPING') {
                $includeShipment = false;
            }
        }

        if ($includeShipment) {
            $amount += $order['invoiceShipping'];
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

        foreach ($order['details'] as $position) {
            if (!in_array($position['id'], $positionIds)) {
                continue;
            }

            $positionAttribute = $position['attribute'];

            $alreadyRefundedAmount = $positionAttribute ? $positionAttribute['fatchipctDebit'] : 0;
            //add difference between total price and already captured amount
            $positionPrice = round($position['price'], 2);

            $amount += ($positionPrice * $position['quantity']) - $alreadyRefundedAmount;

            if ($position['articleNumber'] == 'SHIPPING') {
                $includeShipment = false;
            }
        }

        if ($includeShipment) {
            $amount += $order['invoiceShipping'];
        }

        /*Important: multiply by 100*/
        $amount = round($amount * 100, 2);

        return $amount;
    }

    private function markPositionsAsCaptured($order, $positionIds, $includeShipment = false)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($order['id']);
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
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($order['id']);
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

    private function orderHasComputopPayment($order)
    {
        if (strpos($this->utils->getPaymentNameFromId($order['paymentId']), 'fatchip_computop') !== 0) {
            return false;
        }

        return true;
    }

    private function getCTPaymentClassNameForOrder($order)
    {
        $name = $order['payment']['name'];
        /** @var CTPaymentService $service */
        $paymentMethods = $this->paymentService->getPaymentMethods();

        $key = array_search($name, array_column($paymentMethods, 'name'));
        return $paymentMethods[$key]['className'];
    }

    private function getCTPaymentClassForOrder($order)
    {
        $ctOrder = $this->createCTOrderFromSWorder($order);
        $ctOrder->setOrderDesc('');
        $router = $this->Front()->Router();
        $paymentClassName = $this->getCTPaymentClassNameForOrder($order);

        if ($paymentClassName !== 'PaypalExpress' && $paymentClassName !== 'AmazonPay'){
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

    private function inquireAndupdatePaymentStatus($order, $paymentClass)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($order['id']);

        $currentPaymentStatus = $order->getPaymentStatus()->getId();

        //Only when the current payment status = reserved or partly paid, we update the payment status

        if ($currentPaymentStatus == self::PAYMENTSTATUSRESERVED || $currentPaymentStatus == self::PAYMENTSTATUSPARTIALLYPAID) {
            $payID = $order->getAttribute()->getfatchipctPayid();

            $requestParams = $paymentClass->getInquireParams(
                $this->order['attribute']['fatchipctPayid']
            );

            $inquireResponse = $this->plugin->callComputopService($requestParams, $paymentClass, 'Inquire', $paymentClass->getCTInquireURL());


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
}
