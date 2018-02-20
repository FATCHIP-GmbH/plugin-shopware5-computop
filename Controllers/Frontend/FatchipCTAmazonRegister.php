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

use Shopware\FatchipCTPayment\Util;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTAmazonRegister
 */
class Shopware_Controllers_Frontend_FatchipCTAmazonRegister extends Shopware_Controllers_Frontend_Register implements CSRFWhitelistAware
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

    /**
     * init payment controller
     */
    public function init()
    {
        // init method does not exist in
        // SW >5.2
        // SW 5.4 check
        // SW 5.3 check
        // SW 5.2 check
        // SW 5.1 check
        // SW 5.0 check
        // also session property was removed in SW5.2? 5.3

        if (method_exists('Shopware_Controllers_Frontend_Register', 'init')) {
            parent::init();
        }
        // ToDo handle possible Exception
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
    }

    /*
     * Amazon redirects here after Login
     */
    public function loginAction()
    {
        $request = $this->Request();
        $params = $request->getParams();
        // ToDO  move paymentID saving to saveParamsToSession
        $session = Shopware()->Session();

        // unset logged in User Information so we can register
        $session->offsetUnset('sUserId');
        $session->offsetUnset('sRegisterFinished');
        $session->offsetUnset('sRegister');

        $this->saveParamsToSession($params);
        $response = $this->loginComputopAmazon();
        $payID = $response['PayID'];
        $session->offsetSet('fatchipCTPaymentPayID', $payID);
        // Todo better redirect here?
        $this->forward('index', null, null, ['fatchipCTResponse' => $response]);
    }

    public function indexAction()
    {
        $request = $this->Request();
        $params = $request->getParams();
        $session = Shopware()->Session();
        // ToDo check if setting paymentid in sesion was necessary
        //$session->offsetSet('sPaymentID', $this->utils->getPaymentIdFromName('fatchip_computop_amazonpay'));

        // Not Compatible with SW 5.3 since 5.2? verfiy
        if (version_compare(Shopware()->Config()->version, '5.2', '>=')){
            $register = $this->View()->getAssign('errors');
            $errors = array_merge($register['personal'], $register['billing'], $register['shipping']);
        } else {
            $registerArrObj = $this->View()->getAssign('register')->getArrayCopy();
            $register = $this->getArrayFromArrayObjs($registerArrObj);
            $merged_errors = array_merge($register['personal'], $register['billing'], $register['shipping']);
            $errors =  $merged_errors['error_flags'];
        }
        if (!empty($errors)) {
            $errorMessage = 'Fehler bei der Shop Registrierung:<BR>' .
                            'Bitte korrigieren Sie in Ihrem Amazon Konto folgende Angaben:<BR>';
            $this->view->assign('errorMessage', $errorMessage);
            $this->view->assign('errorFields',array_keys($errors));
        }
        $this->view->assign('fatchipCTResponse', $params['fatchipCTResponse']);
        // add a config->toView method which removed sensitive data from view
        $this->view->assign('fatchipCTPaymentConfig', $this->config);
        // load Template to avoid annoying uppercase to _lowercase conversion
        $this->view->loadTemplate('frontend/fatchipCTAmazonRegister/index.tpl');

    }

    public function loginComputopAmazon()
    {
        // ToDO  get countryIso from session instead by calling sGetUserData
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $countryIso = $user['additional']['country']['countryiso'];
        $session = Shopware()->Session();

        // generate transID for payment and save in Session
        mt_srand((double)microtime() * 1000000);
        $transID = (string)mt_rand();
        $transID .= date('yzGis');
        $session->offsetSet('fatchipCTPaymentTransID', $transID);


        $service = new \Fatchip\CTPayment\CTAmazon($this->config);
        $requestParams = $service->getAmazonLGNParams(
            $session->fatchipCTPaymentTransID,
            $session->fatchipCTAmazonAccessToken,
            $session->fatchipCTAmazonAccessTokenType,
            $session->fatchipCTAmazonAccessTokenExpire,
            $session->fatchipCTAmazonAccessTokenScope,
            $countryIso,
            'https://testshop.de/FatchipCTPayment/notify'
        );
        // wrap this in a method we can hook for central logging
        // refactor Amazon to use central Paymentservice to get rid of service Param
        $response = $this->plugin->callComputopService($requestParams, $service);
        return $response;
    }

    /**
     * not used anymore
     * simply show an error after
     * parent::saveRegisterAction forwards to our index
     * DEsc:
     * This is only implemented to
     * get registration exceptions and errors
     *
     * @return void
     */
    /*    public function saveRegisterAction()
        {
            parent::saveRegisterAction();

            // check for registration errors and log those
            // sadly we can not use our central logging subscriber
            // because saveRegister forwards to checkout/index in case of errors
            if ($this->error){
                if ($this->View()->hasTemplate()){
                    $registerArrObj = $this->View()->getAssign('register')->getArrayCopy();
                    $register = $this->getArrayFromArrayObjs($registerArrObj);

                }

            }
            $testifaboveReturns = 'blubs';
        }
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

    public function getWhitelistedCSRFActions()
    {
        $returnArray = array(
            'saveRegister',
        );
        return $returnArray;
    }
}


