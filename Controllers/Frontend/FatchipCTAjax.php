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

    /** @var Util $utils * */
    protected $utils;

    public function init()
    {
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

    }


    // ToDo leave Actions here, but move request response handling to payment service
    public function ctSetOrderDetailsAction()
    {

        $session = Shopware()->Session();
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        $amount = $basket['AmountNumeric'] * 100;
        $currency = 'EUR';
        $orderDesc = "Test";

        $service = new \Fatchip\CTPayment\CTAmazon($this->config);
        $requestParams = $service->getAmazonSODParams(
            $session->offsetGet('fatchipCTPaymentPayID'),
            $session->offsetGet('fatchipCTPaymentTransID'),
            $amount,
            $currency,
            $orderDesc,
            $referenceId
        );

        // wrap this in a method we can hook for central logging
        // refactor Amazon to use central Paymentservice to get rid of service Param
        $response = $this->plugin->callComputopService($requestParams, $service);
        $data = [];
        $data['data'] = $response;
        $data['status'] = ($response['Code'] == '00000000' ? 'success' : 'error');
        $data['errormessage'] = $response['Description'];
        $encoded = json_encode($data);
        echo $encoded;
    }

    /* return error in case country of shippingaddress
     * is not supported as delivery country
     *
     */
    public function ctIsShippingCountrySupportedAction()
    {
        $params = $this->Request()->getParams();
        $shippingCountryID = $params['shippingCountryID'];
        $supportedShippingCountries = $this->getAllowedShippingCountries();
        $data = [];
        // ToDO refactor
        if (in_array($shippingCountryID, $supportedShippingCountries)) {
            $data['status'] = 'success';
        } else {
            $data['status'] = 'error';
            $data['errormessage'] = 'Dieses Lieferland wird vom Shop nicht unterstützt.
            Bitte wählen Sie eine andere Addresse';
        }

        $encoded = json_encode($data);
        echo $encoded;
    }

    // ToDO Move to Utils? Does this work with subshops?
    public function getAllowedShippingCountries()
    {
        $activeCountries = $this->get('models')
            ->getRepository('Shopware\Models\Country\Country')
            ->findBy(['active' => true]);
        $allowedCountries = array_map(function ($el) {
            /** @var \Shopware\Models\Country\Country $el */
            return $el->getId();
        }, $activeCountries);
        return $allowedCountries;
    }

    public function ctGetOrderDetailsAction()
    {
        $session = Shopware()->Session();
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        // save referencID to Session
        $session->offsetSet('fatchipCTAmazonReferenceID', $referenceId);
        $orderDesc = "Test";

        $service = new \Fatchip\CTPayment\CTAmazon($this->config);
        $requestParams = $service->getAmazonGODParams(
            $session->offsetGet('fatchipCTPaymentPayID'),
            $orderDesc,
            $referenceId
        );
        // wrap this in a method we can hook for central logging
        // refactor Amazon to use central Paymentservice to get rid of service Param
        $response = $this->plugin->callComputopService($requestParams, $service);

        // Test data IT, Rome, Guiseppe Rossi needs this
        if (!$response['AddrStreet2'] && !empty($response['addrstreet2'])) {
            $response['AddrStreet2'] = $response['addrstreet2'];
        }

        // Test data GB, London, Elisabeth Harrison needs this
        if (!$response['AddrStreet2'] && !empty($response['AddrStreet'])) {
            $response['AddrStreet2'] = $response['AddrStreet'];
        }
        // replace country code with shopware countryId
        $response['AddrCountryCodeID'] = $this->utils->getCountryIdFromIso($response['AddrCountryCode']);
        $response['bdaddrcountrycodeID'] = $this->utils->getCountryIdFromIso($response['bdaddrcountrycode']);
        $data = [];
        $data['data'] = $response;
        $data['status'] = ($response['Code'] == '00000000' ? 'success' : 'error');
        $data['errormessage'] = $response['Description'];
        $encoded = json_encode($data);
        echo $encoded;
    }
}
