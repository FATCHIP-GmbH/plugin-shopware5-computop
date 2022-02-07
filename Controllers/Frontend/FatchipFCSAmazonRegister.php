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
 * @link       https://www.firstcashsolution.de/
 */

use Fatchip\CTPayment\CTPaymentMethodIframe;
use Shopware\Plugins\FatchipFCSPayment\Util;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSAmazonRegister
 *
 * @category  Payment_Controller
 * @package   First Cash Solution_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 First Cash Solution
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.firstcashsolution.de/
 */
class Shopware_Controllers_Frontend_FatchipFCSAmazonRegister extends Shopware_Controllers_Frontend_Register implements CSRFWhitelistAware
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
     * Init payment controller.
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {
        if (method_exists('Shopware_Controllers_Frontend_Register', 'init')) {
            parent::init();
        }
        $this->paymentService = Shopware()->Container()->get('FatchipFCSPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipFCSPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipFCSPaymentUtils');
    }

    /**
     * Amazon redirects here after Login.
     *
     * @return void
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
        $session->offsetSet('fatchipFCSPaymentPayID', $response->getPayID());
        $this->forward('index', null, null, ['fatchipFCSResponse' => $response]);
    }

    /**
     * Replaces the standard shopware user registration with the amazon widgets.
     *
     * Users are forwarded to this action after returning from amazonpay login
     *
     * @return void
     */
    public function indexAction()
    {
        $session = Shopware()->Session();
        // this has to be set so shipping methods will work
        $session->offsetSet('sPaymentID', $this->utils->getPaymentIdFromName('fatchip_firstcash_amazonpay'));

        if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $register = $this->View()->getAssign('errors');
            $errors = array_merge($register['personal'], $register['billing'], $register['shipping']);
        } else {
            $registerArrObj = $this->View()->getAssign('register')->getArrayCopy();
            $register = $this->getArrayFromArrayObjs($registerArrObj);
            $merged_errors = array_merge($register['personal'], $register['billing'], $register['shipping']);
            $errors = $merged_errors['error_flags'];
        }
        if (!empty($errors)) {
            $errorMessage = Shopware()->Snippets()
                ->getNamespace('frontend/FatchipFCSPayment/translations')
                ->get('errorRegister');
            $this->view->assign('errorMessage', $errorMessage);
            $this->view->assign('errorFields', array_keys($errors));
        }
        // TODO  add a config->toView method which removed sensitive data from view
        $this->view->assign('fatchipFCSPaymentConfig', $this->config);
        $this->view->loadTemplate('frontend/fatchipFCSAmazonRegister/index.tpl');
    }

    /**
     *  Calls the computop api with AmazonLGN after user log-ins.
     *
     * @return \Fatchip\CTPayment\CTResponse
     */
    public function loginComputopAmazon()
    {
        // TODO  get countryIso from session instead by calling sGetUserData
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $countryIso = $user['additional']['country']['countryiso'];
        $router = $this->Front()->Router();
        $session = Shopware()->Session();

        $transID = CTPaymentMethodIframe::generateTransID();
        $session->offsetSet('fatchipFCSPaymentTransID', $transID);

        $payment = $this->paymentService->getPaymentClass('AmazonPay');
        $requestParams = $payment->getAmazonLGNParams(
            $session->fatchipFCSPaymentTransID,
            $session->fatchipFCSAmazonAccessToken,
            $session->fatchipFCSAmazonAccessTokenType,
            $session->fatchipFCSAmazonAccessTokenExpire,
            $session->fatchipFCSAmazonAccessTokenScope,
            $countryIso,
            $router->assemble(['controller' => 'FatchipFCSAmazon', 'action' => 'notify', 'forceSecure' => true])
        );
        $requestParams['EtiId'] = $this->utils->getUserDataParam();
        return $this->plugin->callComputopService($requestParams, $payment, 'LGN', $payment->getCTPaymentURL());
    }

    /**
     * Converts arrayObjects from view template to an accessible array.
     *
     * @param array $arrayObjs Enlight_View_Default->getAssign()->toArray()
     *
     * @see    Enlight_View_Default::getAssign()
     * @return array
     */
    private function getArrayFromArrayObjs($arrayObjs)
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
     * Saves relevant amazon tokens in user session.
     *
     * @param array $params amazon tokens obtained after successful login
     *
     * @return void
     */
    private function saveParamsToSession($params)
    {
        $session = Shopware()->Session();

        if (!empty($params["access_token"])) {
            $session->offsetSet('fatchipFCSAmazonAccessToken', $params["access_token"]);
        }
        if (!empty($params["token_type"])) {
            $session->offsetSet('fatchipFCSAmazonAccessTokenType', $params["token_type"]);
        }
        if (!empty($params["expires_in"])) {
            $session->offsetSet('fatchipFCSAmazonAccessTokenExpire', $params["expires_in"]);
        }
        if (!empty($params["scope"])) {
            $session->offsetSet('fatchipFCSAmazonAccessTokenScope', $params["scope"]);
        }
    }

    /**
     * {inheritdoc}
     *
     * @return array
     */
    public function getWhitelistedCSRFActions()
    {
        $returnArray = array(
            'saveRegister',
        );
        return $returnArray;
    }
}


