<?php

/**
 * The First Cash Solution Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The First Cash Solution Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with First Cash Solution Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */

use Shopware\Plugins\FatchipFCSPayment\Util;

/**
 * Shopware_Controllers_Frontend_FatchipFCSAjax
 *
 * Frontend controller for Ajax communication
 *
 * @category   Payment_Controller
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */
class Shopware_Controllers_Frontend_FatchipFCSAjax extends Enlight_Controller_Action
{

    /**
     * Fatchip PaymentService
     *
     * @var \Fatchip\CTPayment\CTPaymentService $service
     */
    protected $paymentService;

    /**
     * FatchipFCSpayment Plugin Bootstrap Class
     *
     * @var Shopware_Plugins_Frontend_FatchipFCSPayment_Bootstrap
     */
    protected $plugin;

    /**
     * FatchipFCSPayment plugin settings
     *
     * @var array
     */
    protected $config;

    /**
     * FatchipFCSPaymentUtils
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
        $this->paymentService = Shopware()->Container()->get('FatchipFCSPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipFCSPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipFCSPaymentUtils');
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
            $this->session->offsetGet('fatchipFCSPaymentPayID'),
            $this->session->offsetGet('fatchipFCSPaymentTransID'),
            $amount,
            $currency,
            $orderDesc,
            $referenceId
        );
        $requestParams['EtiId'] = $this->utils->getUserDataParam();

        $response = $this->plugin->callComputopService($requestParams, $payment, 'SCO', $payment->getCTPaymentURL())->toArray();

        if($response['Code'] != '00000000') {
            $this->Response()->setHttpResponseCode(418);
            return;
        }

        $this->session->offsetSet('fatchipFCSPaymentSCOValid', true);

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
        $this->session->offsetSet('fatchipFCSAmazonReferenceID', $referenceId);
        $orderDesc = "Test";

        $payment = $this->paymentService->getPaymentClass('AmazonPay');

        $requestParams = $payment->getAmazonGODParams(
            $this->session->offsetGet('fatchipFCSPaymentPayID'),
            $orderDesc,
            $referenceId
        );
        $requestParams['EtiId'] = $this->utils->getUserDataParam();

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
            $this->session->offsetGet('fatchipFCSPaymentPayID'),
            $this->session->offsetGet('fatchipFCSPaymentTransID'),
            $amount,
            $currency,
            $orderDesc,
            $referenceId
        );
        $requestParams['EtiId'] = $this->utils->getUserDataParam();

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
            $data['errormessage'] = Shopware()->Snippets()
                ->getNamespace('frontend/FatchipFCSPayment/translations')
                ->get('errorShippingCountry');
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
