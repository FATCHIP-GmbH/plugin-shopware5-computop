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
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTAmazonRegister
 */
class Shopware_Controllers_Frontend_FatchipCTAmazonRegister extends Shopware_Controllers_Frontend_Register implements CSRFWhitelistAware
{

    /**
     * PaymentService
     * @var \Fatchip\CTPayment\CTPaymentService $service
     */
    protected $paymentService;

    /**
     * FatchipCTpayment Plugin Bootstrap Class
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    /**
     * Array containing the pluginsettings
     * @var array
     */
    protected $config;

    /** @var Util $utils * */
    protected $utils;

    /**
     * init payment controller
     */
    public function init()
    {
        if (method_exists('Shopware_Controllers_Frontend_Register', 'init')) {
            parent::init();
        }
        // ToDo handle possible Exception
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
    }

    /**
     * Amazon redirects here after Login
     */
    public function loginAction()
    {
        $params = $this->Request()->getParams();
        $session = Shopware()->Session();

        // unset logged in User Information so we can register
        $session->offsetUnset('sUserId');
        $session->offsetUnset('sRegisterFinished');
        $session->offsetUnset('sRegister');

        $this->saveParamsToSession($params);
        $response = $this->loginComputopAmazon();
        $session->offsetSet('fatchipCTPaymentPayID', $response->getPayID());
        $this->forward('index', null, null, ['fatchipCTResponse' => $response]);
    }

    /**
     *  replaces the standard shopware user registration with the amazon widgets
     *
     * users are forwarded to this action after returning from amazonpay login
     */
    public function indexAction()
    {
        $session = Shopware()->Session();
        // this has to be set so shipping methods will work
        $session->offsetSet('sPaymentID', $this->utils->getPaymentIdFromName('fatchip_computop_amazonpay'));

        // Not Compatible with SW 5.3 since 5.2? verfiy
        // ToDO check if Version Check work for all SW Versions
        if (version_compare(\Shopware::VERSION, '5.2', '>=')) {
            $register = $this->View()->getAssign('errors');
            $errors = array_merge($register['personal'], $register['billing'], $register['shipping']);
        } else {
            $registerArrObj = $this->View()->getAssign('register')->getArrayCopy();
            $register = $this->getArrayFromArrayObjs($registerArrObj);
            $merged_errors = array_merge($register['personal'], $register['billing'], $register['shipping']);
            $errors = $merged_errors['error_flags'];
        }
        if (!empty($errors)) {
            $errorMessage = 'Fehler bei der Shop Registrierung:<BR>' .
                'Bitte korrigieren Sie in Ihrem Amazon Konto folgende Angaben:<BR>';
            $this->view->assign('errorMessage', $errorMessage);
            $this->view->assign('errorFields', array_keys($errors));
        }
        // unused
        //$this->view->assign('fatchipCTResponse', $params['fatchipCTResponse']);
        // add a config->toView method which removed sensitive data from view
        $this->view->assign('fatchipCTPaymentConfig', $this->config);
        // load Template to avoid annoying uppercase to _lowercase conversion
        $this->view->loadTemplate('frontend/fatchipCTAmazonRegister/index.tpl');

    }

    /**
     *  calls the computop api with AmazonLGN after user log-ins
     *
     * @return \Fatchip\CTPayment\CTResponse
     */
    public function loginComputopAmazon()
    {
        // ToDO  get countryIso from session instead by calling sGetUserData
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $countryIso = $user['additional']['country']['countryiso'];
        $router = $this->Front()->Router();
        $session = Shopware()->Session();

        // generate transID for payment and save in Session
        mt_srand((double)microtime() * 1000000);
        $transID = (string)mt_rand();
        $transID .= date('yzGis');
        $session->offsetSet('fatchipCTPaymentTransID', $transID);

        /** @var \Fatchip\CTPayment\CTPaymentMethods\AmazonPay $payment */
        $payment = $this->paymentService->getPaymentClass('AmazonPay', $this->config);
        $requestParams = $payment->getAmazonLGNParams(
            $session->fatchipCTPaymentTransID,
            $session->fatchipCTAmazonAccessToken,
            $session->fatchipCTAmazonAccessTokenType,
            $session->fatchipCTAmazonAccessTokenExpire,
            $session->fatchipCTAmazonAccessTokenScope,
            $countryIso,
            $router->assemble(['controller' => 'FatchipCTAmazon', 'action' => 'notify', 'forceSecure' => true])
        );
        return $this->plugin->callComputopService($requestParams, $payment, 'LGN', $payment->getCTPaymentURL());
    }

    /**
     * converts arrayObjects from view template to an accessible array
     *
     * @param $arrayObjs
     * @return array
     */
    public function getArrayFromArrayObjs($arrayObjs)
    {
        $array = [];
        foreach ($arrayObjs as $key => $arrayObj) {
            $array[$key] = $arrayObj->getArrayCopy();
            foreach ($array[$key] as $arrayObjKey => $value) {
                $array[$key][$arrayObjKey] = $value->getArrayCopy();
            }
        }
        return $array;
    }

    /**
     * saves relevant amazon tokens in user session
     *
     * @param $params
     */
    public function saveParamsToSession($params)
    {
        $session = Shopware()->Session();

        if (!empty($params["access_token"])) {
            $session->offsetSet('fatchipCTAmazonAccessToken', $params["access_token"]);
        }
        if (!empty($params["token_type"])) {
            $session->offsetSet('fatchipCTAmazonAccessTokenType', $params["token_type"]);
        }
        if (!empty($params["expires_in"])) {
            $session->offsetSet('fatchipCTAmazonAccessTokenExpire', $params["expires_in"]);
        }
        if (!empty($params["scope"])) {
            $session->offsetSet('fatchipCTAmazonAccessTokenScope', $params["scope"]);
        }
    }

    /**
     * {inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        $returnArray = array(
            'saveRegister',
        );
        return $returnArray;
    }
}


