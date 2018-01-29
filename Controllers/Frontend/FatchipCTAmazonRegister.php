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
use Fatchip\CTPayment\CTOrder\CTOrder;

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

    public function indexAction()
    {
        // Debug:
        $request = $this->Request();
        $params = $request->getParams();

        // ToDo setting all params in session for later use in Templates
        // check if all are neccessary

        $this->saveParamsToSession($params);
        // $this->initComputopamazon();

        $test = new \Fatchip\CTPayment\CTAmazonLoginService($this->config);
        $response = $test->computopInit($params["access_token"], $params["token_type"], $params["expires_in"],$params["scope"]);
        // ToDo check: for now we only use Information we get returned from Paygate in view
        //$this->view->assign('')
        $this->view->assign('fatchipCTPaymentConfig', $this->config);
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


