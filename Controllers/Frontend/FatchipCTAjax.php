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


class Shopware_Controllers_Frontend_FatchipCTAjax extends Enlight_Controller_Action
{

    /** @var \Fatchip\CTPayment\CTPaymentService $service */
    protected $paymentService;

    /**
     * FatchipCTpayment Plugin Bootstrap Class
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    /**
     * {inheritdoc}
     */
    protected $config;

    /** @var Util $utils * */
    protected $utils;

    protected $session;

    public function init()
    {
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
        $this->session = Shopware()->Session();

    }

    public function ctGetOrderDetailsAction()
    {
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        $this->session->offsetSet('fatchipCTAmazonReferenceID', $referenceId);
        $orderDesc = "Test";

        /** @var \Fatchip\CTPayment\CTPaymentMethods\AmazonPay $payment */
        $payment = $this->paymentService->getPaymentClass('AmazonPay', $this->config);

        $requestParams = $payment->getAmazonGODParams(
            $this->session->offsetGet('fatchipCTPaymentPayID'),
            $orderDesc,
            $referenceId
        );

        $response = $this->plugin->callComputopService($requestParams, $payment, 'GOD', $payment->getCTPaymentURL())->toArray();
        // Test data IT, Rome, Guiseppe Rossi needs this
        if (!$response['AddrStreet2'] && !empty($response['addrstreet2'])) {
            $response['AddrStreet2'] = $response['addrstreet2'];
        }

        // Test data GB, London, Elisabeth Harrison needs this
        if (!$response['AddrStreet2'] && !empty($response['AddrStreet'])) {
            $response['AddrStreet2'] = $response['AddrStreet'];
        }

        $response['AddrCountryCodeID'] = $this->utils->getCountryIdFromIso($response['AddrCountryCode']);
        $response['bdaddrcountrycodeID'] = $this->utils->getCountryIdFromIso($response['bdaddrcountrycode']);
        $data = [];
        $data['data'] = $response;
        $data['status'] = ($response['Code'] == '00000000' ? 'success' : 'error');
        $data['errormessage'] = $response['Description'];
        $encoded = json_encode($data);
        echo $encoded;
    }

    // ToDo leave Actions here, but move request response handling to payment service
    public function ctSetOrderDetailsAction()
    {
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        $amount = $basket['AmountNumeric'] * 100;
        // ToDo use REAL Currency !!!!
        $currency = 'EUR';
        $orderDesc = "Test";

        /** @var \Fatchip\CTPayment\CTPaymentMethods\AmazonPay $payment */
        $payment = $this->paymentService->getPaymentClass('AmazonPay', $this->config);

        $requestParams = $payment->getAmazonSODParams(
            $this->session->offsetGet('fatchipCTPaymentPayID'),
            $this->session->offsetGet('fatchipCTPaymentTransID'),
            $amount,
            $currency,
            $orderDesc,
            $referenceId
        );

        $response = $this->plugin->callComputopService($requestParams, $payment, 'SOD', $payment->getCTPaymentURL())->toArray();
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
}
