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

    /** @var Util $utils **/
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

        if (method_exists('Shopware_Controllers_Frontend_Register','init')){
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

        $this->saveParamsToSession($params);
        $response = $this->loginComputopAmazon();
        $payID = $response['PayID'];
        $session->offsetSet('fatchipCTPaymentPayID', $payID);
        $this->forward('index', null , null , ['fatchipCTResponse' => $response]);
    }

    public function indexAction()
    {
        $request = $this->Request();
        $params = $request->getParams();
        $session = Shopware()->Session();
        $session->offsetSet('sPaymentID', $this->utils->getPaymentIdFromName('fatchip_computop_amazonpay'));

        $this->view->assign('fatchipCTResponse', $params['fatchipCTResponse']);
        $this->view->assign('fatchipCTPaymentConfig', $this->config);

    }

    public function loginComputopAmazon(){
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
        $requestParams =  $service->getAmazonLGNParams(
            $session->fatchipCTPaymentTransID,
            $session->fatchipCTAmazonAccessToken,
            $session->fatchipCTAmazonAccessTokenType,
            $session->fatchipCTAmazonAccessTokenExpire,
            $session->fatchipCTAmazonAccessTokenScope,
            $countryIso,
            'https://testshop.de/FatchipCTPayment/notify'
        );
        return $service->callComputopAmazon($requestParams);
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


