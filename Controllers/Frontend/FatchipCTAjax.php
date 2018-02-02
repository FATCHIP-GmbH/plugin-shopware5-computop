<?php

use Shopware\FatchipCTPayment\Util;


class Shopware_Controllers_Frontend_FatchipCTAjax extends Enlight_Controller_Action
{

    /** @var \Fatchip\CTPayment\CTPaymentService $service */
    protected $paymentService;

    /**
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    protected $config;

    /** @var Util $utils **/
    protected $utils;

    public function init()
    {
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
    }



    // ToDo leave Actions here, but move request response handling to payment service
    public function ctSetOrderDetailsAction(){
        // disable templates for all controller actions
        // ToDO move to predispatch?
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

        $session = Shopware()->Session();
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        $amount = $basket['AmountNumeric'] * 100;
        $currency = 'EUR';
        $orderDesc = "Test";

        $service = new \Fatchip\CTPayment\CTAmazon($this->config);
        $requestParams =  $service->getAmazonSODParams(
            $session->offsetGet('fatchipCTPaymentPayID'),
            $session->offsetGet('fatchipCTPaymentTransID'),
            $amount,
            $currency,
            $orderDesc,
            $referenceId
        );
        $response = $service->callComputopAmazon($requestParams);
        $data = [];
        $data['data'] = $response;
        $data['status'] = ($response[7] == 'Status=FAILED' ) ? 'error' : 'success';
        $data['errormessage'] = $response[5];
        $encoded = json_encode($data);
        echo $encoded;
    }

    public function ctGetOrderDetailsAction(){
        // disable templates for all controller actions
        // ToDO move to predispatch?
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

        $session = Shopware()->Session();
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        // save referencID to Session
        $session->offsetSet('fatchipCTAmazonReferenceID', $referenceId);
        $orderDesc = "Test";

        $service = new \Fatchip\CTPayment\CTAmazon($this->config);
        $requestParams =  $service->getAmazonGODParams(
            $session->offsetGet('fatchipCTPaymentPayID'),
            $orderDesc,
            $referenceId
        );
        $response = $service->callComputopAmazon($requestParams);
        $data = [];
        $data['data'] = $response;
        $data['status'] = ($response[7] == 'Status=FAILED' ) ? 'error' : 'success';
        $data['errormessage'] = $response[5];
        $encoded = json_encode($data);
        echo $encoded;
    }
}
