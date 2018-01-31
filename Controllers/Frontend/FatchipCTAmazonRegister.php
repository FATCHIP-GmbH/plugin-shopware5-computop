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

/**
 * Class Shopware_Controllers_Frontend_FatchipCTAmazonRegister
 */
class Shopware_Controllers_Frontend_FatchipCTAmazonRegister extends Shopware_Controllers_Frontend_Register
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
        parent::init();
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
        // Debug:
        $request = $this->Request();
        $params = $request->getParams();

        // either use session or $params. decisions decisions ...
        $this->saveParamsToSession($params);
        $response = $this->loginComputopAmazon();
        $keyVal = explode('=',$response[1]);
        $payID = $keyVal[1];
        // save PayID in session
        $this->session->offsetSet('fatchipCTPaymentPayID', $payID);
        // forward to index this will display registration page with Amazon wallet widget
        $this->forward('index', null , null , ['fatchipCTResponse' => $response]);
    }

    public function indexAction()
    {
        // Debug:
        $request = $this->Request();
        $params = $request->getParams();

        $this->view->assign('fatchipCTResponse', $params['fatchipCTResponse']);
        $this->view->assign('fatchipCTPaymentConfig', $this->config);

    }

    public function loginComputopAmazon(){
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $countryIso = $user['additional']['country']['countryiso'];
        $amount = $basket['AmountNumeric'] * 100;
        $currency = 'EUR';

        // generate transID for payment and save in Session
        mt_srand((double)microtime() * 1000000);
        $transID = (string)mt_rand();
        $transID .= date('yzGis');
        $this->session->offsetSet('fatchipCTPaymentTransID', $transID);


        $service = new \Fatchip\CTPayment\CTAmazon($this->config);
        $requestParams =  $service->getAmazonLGNParams(
            $this->session->fatchipCTPaymentTransID,
            $this->session->fatchipCTAmazonAccessToken,
            $this->session->fatchipCTAmazonAccessTokenType,
            $this->session->fatchipCTAmazonAccessTokenExpire,
            $this->session->fatchipCTAmazonAccessTokenScope,
            $countryIso,
            'https://testshop.de/FatchipCTPayment/notify'
        );
        return $service->callComputopAmazon($requestParams);
    }

    public function saveParamsToSession($params)
    {
        if (!empty($params["access_token"])) {
            $this->session->offsetSet('fatchipCTAmazonAccessToken', $params["access_token"]);
        }
        if (!empty($params["token_type"])) {
            $this->session->offsetSet('fatchipCTAmazonAccessTokenType', $params["token_type"]);
        }
        if (!empty($params["expires_in"])) {
            $this->session->offsetSet('fatchipCTAmazonAccessTokenExpire', $params["expires_in"]);
        }
        if (!empty($params["scope"])) {
            $this->session->offsetSet('fatchipCTAmazonAccessTokenScope', $params["scope"]);
        }
    }
}


