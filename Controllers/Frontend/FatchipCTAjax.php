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

/**
 * Shopware_Controllers_Frontend_FatchipCTAjax
 *
 * Frontend controller for Ajax communication
 *
 * @category   Payment_Controller
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */
class Shopware_Controllers_Frontend_FatchipCTAjax extends Enlight_Controller_Action
{

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
     * Class contructor.
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
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
        $this->session = Shopware()->Session();
    }

    /**
     * Calls the computop api with amazonpay SCO api call.
     * returns  api response json encoded for use with JQuery plugins
     *
     * @see    AmazonPay::getAmazonSCOParams()
     * @return void
     */
    public function ctAmznSetOrderDetailsAndConfirmOrderAction()
    {
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        $basket = Shopware()->Session()->sOrderVariables;
        $amount = $basket["sBasket"]["AmountNumeric"] * 100;

        // ToDo use REAL Currency and OrderDesc !!!!
        $currency = $basket["sBasket"]["sCurrencyName"];
        $orderDesc = "Test";

        $payment = $this->paymentService->getPaymentClass('AmazonPay');

        $requestParams = $payment->getAmazonSCOParams(
            $this->session->offsetGet('fatchipCTPaymentPayID'),
            $this->session->offsetGet('fatchipCTPaymentTransID'),
            $amount,
            $currency,
            $orderDesc,
            $referenceId
        );
        $requestParams['EtId'] = $this->utils->getUserDataParam();

        $response = $this->plugin->callComputopService($requestParams, $payment, 'SCO', $payment->getCTPaymentURL())->toArray();

        if($response['Code'] != '00000000') {
            $this->Response()->setHttpResponseCode(418);
            return;
        }

        $this->session->offsetSet('fatchipCTPaymentSCOValid', true);

        echo json_encode('OK');
    }
    /**
     * Calls the computop api with amazonpay GOD api call.
     * returns  api response json encoded for use with JQuery plugins
     *
     * @see    AmazonPay::getAmazonGODParams()
     * @return void
     */
    public function ctGetOrderDetailsAction()
    {
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        $this->session->offsetSet('fatchipCTAmazonReferenceID', $referenceId);
        $orderDesc = "Test";

        $payment = $this->paymentService->getPaymentClass('AmazonPay');

        $requestParams = $payment->getAmazonGODParams(
            $this->session->offsetGet('fatchipCTPaymentPayID'),
            $orderDesc,
            $referenceId
        );
        $requestParams['EtId'] = $this->utils->getUserDataParam();

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

    /**
     * Calls the computop api with amazonpay SOD api call.
     * returns api response as json encoded for use with JQuery plugins
     *
     * @see    AmazonPay::getAmazonSODParams()
     * @return void
     * @throws Exception
     */
    public function ctSetOrderDetailsAction()
    {
        $params = $this->Request()->getParams();
        $referenceId = $params['referenceId'];
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        $amount = $basket['AmountNumeric'] * 100;
        // ToDo use REAL Currency and OrderDesc !!!!
        $currency = 'EUR';
        $orderDesc = "Test";

        $payment = $this->paymentService->getPaymentClass('AmazonPay');

        $requestParams = $payment->getAmazonSODParams(
            $this->session->offsetGet('fatchipCTPaymentPayID'),
            $this->session->offsetGet('fatchipCTPaymentTransID'),
            $amount,
            $currency,
            $orderDesc,
            $referenceId
        );
        $requestParams['EtId'] = $this->utils->getUserDataParam();

        $response = $this->plugin->callComputopService($requestParams, $payment, 'SOD', $payment->getCTPaymentURL())->toArray();
        $data = [];
        $data['data'] = $response;
        $data['status'] = ($response['Code'] == '00000000' ? 'success' : 'error');
        $data['errormessage'] = $response['Description'];
        $encoded = json_encode($data);
        echo $encoded;
    }

    /**
     * Checks if shipping country of amazon address is supported by the shop.
     * returns success or error json encoded for use with JQuery plugins
     *
     * @see    getAllowedShippingCountries()
     * @return void
     */
    public function ctIsShippingCountrySupportedAction()
    {
        $params = $this->Request()->getParams();
        $shippingCountryID = $params['shippingCountryID'];
        $supportedShippingCountries = $this->getAllowedShippingCountries();
        $data = [];

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

    /**
     *  Returns all shipping countries allowed in shopware configuration.
     *
     * @return array
     */
    private function getAllowedShippingCountries()
    {
        $activeCountries = $this->get('models')
            ->getRepository('Shopware\Models\Country\Country')
            ->findBy(['active' => true]);
        $allowedCountries = array_map(
            function ($el) {
                return $el->getId();
            }, $activeCountries
        );
        return $allowedCountries;
    }
}
