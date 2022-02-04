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
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */

use Shopware\Plugins\FatchipFCSPayment\Util;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSAmazonRegister
 *
 * @category  Payment_Controller
 * @package   Computop_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 Computop
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.firstcash.com
 */
class Shopware_Controllers_Frontend_FatchipFCSAmazonCheckout extends Shopware_Controllers_Frontend_Checkout implements CSRFWhitelistAware
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
     * Init payment controller
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {
        // init method may or may not exists depending on sw version
        if (method_exists(parent::init())) {
            parent::init();
        }
        // ToDo handle possible Exception
        $this->paymentService = Shopware()->Container()->get('FatchipFCSPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipFCSPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipFCSPaymentUtils');
    }

    /**
     *  Extends shippingPayment with custom template
     *
     * @return void|Enlight_View_Default
     */
    public function shippingPaymentAction()
    {
        parent::shippingPaymentAction();
        $params = $this->Request()->getParams();
        $fatchipFCSAmazonpayID = $this->utils->getPaymentIdFromName('fatchip_firstcash_amazonpay');

        $this->view->assign('fatchipFCSAmazonpayID', $fatchipFCSAmazonpayID);
        $this->view->assign('fatchipFCSResponse', $params['fatchipFCSResponse']);
        $this->view->assign('fatchipFCSPaymentConfig', $this->config);
        $this->view->assign('sStepActive', 'paymentShipping');

        // override template with ours for xhr requests
        if ($this->Request()->getParam('isXHR')) {
            return $this->view->loadTemplate('frontend/fatchipFCSAmazonCheckout/fatchip_shipping_payment_core.tpl');
        }

        // load Template to avoid annoying uppercase to _lowercase conversion
        $this->view->loadTemplate('frontend/fatchipFCSAmazonCheckout/shipping_payment.tpl');
    }

    /**
     * {inheritdoc}
     *
     * @return array
     */
    public function getWhitelistedCSRFActions()
    {
        $returnArray = array(
            'shippingPayment',
        );
        return $returnArray;
    }

    /**
     *  Extends confirm with custom template
     *
     * @return void|Enlight_View_Default
     */
    public function confirmAction()
    {
        $userId = Shopware()->Session()->get('sUserId');

        $this->unsetAmazonFakeBirthday($userId);

        $this->view->assign('fatchipFCSPaymentConfig', $this->config);
        $this->view->assign('fatchipFCSAmazonReferenceID', Shopware()->Session()->offsetGet('fatchipFCSAmazonReferenceID'));

        parent::confirmAction();
        $this->view->loadTemplate('frontend/fatchipFCSAmazonCheckout/confirm.tpl');
    }

    /**
     *  Extends finish with custom template
     *  Also unsets all session variables and refreshes basket
     *
     * @throws Exception
     * @return void
     */
    public function finishAction()
    {
        parent::finishAction();
        $this->view->loadTemplate('frontend/fatchipFCSAmazonCheckout/finish.tpl');
        Shopware()->Session()->unsetAll();
        Shopware()->Modules()->Basket()->sRefreshBasket();
    }

    /**
     *  Get amazon order information from computop api
     *
     * @return \Fatchip\CTPayment\CTResponse $response
     */
    public function ctGetOrderDetails()
    {
        $session = Shopware()->Session();
        // TODO use default orderDesc
        $orderDesc = "Test";

        $payment = $this->paymentService->getPaymentClass('AmazonPay');
        $requestParams = $payment->getAmazonGODParams(
            $session->offsetGet('fatchipFCSPaymentPayID'),
            $orderDesc,
            $session->offsetGet('fatchipFCSAmazonReferenceID')
        );
        $requestParams['EtiId'] = $this->utils->getUserDataParam();
        $response = $this->plugin->callComputopService($requestParams, $payment, 'GOD', $payment->getCTPaymentURL());
        return $response;
    }

    /**
     * Amazon is not providing birthday. If birthday is required by config for shopware user registration
     * we do have to bypass this verification by adding a fake date at registration and removing it afterwards
     *
     * @param $userId
     */
    public function unsetAmazonFakeBirthday($userId) {

        if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $sql = "UPDATE shopware.s_user SET birthday = NULL WHERE id = ? AND birthday = '1901-02-28'";
        }
        else {
            $sql = "UPDATE shopware.s_user_billingaddress SET birthday = NULL WHERE userID = ? AND birthday = '1901-02-28'";
        }

        try {
            Shopware()->Db()->query($sql, array($userId));
        }
        catch(\Exception $e) {
            //if for whatever reasons deleting birthday is not possible, we don't want to break the order process
        }
    }
}


