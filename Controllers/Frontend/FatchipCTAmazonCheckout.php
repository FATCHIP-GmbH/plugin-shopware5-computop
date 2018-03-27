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
 * PHP version 5.6, 7.0 , 7.1
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
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTAmazonRegister
 */
class Shopware_Controllers_Frontend_FatchipCTAmazonCheckout extends Shopware_Controllers_Frontend_Checkout implements CSRFWhitelistAware
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
        // init method may or may not exists depending on sw version
        if (method_exists(parent::init())) {
            parent::init();
        }
        // ToDo handle possible Exception
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
    }

    /**
     *  extends shippingPayment with custom template
     *
     * @return void|Enlight_View_Default
     */
    public function shippingPaymentAction()
    {
        parent::shippingPaymentAction();
        $params = $this->Request()->getParams();
        $fatchipCTAmazonpayID = $this->utils->getPaymentIdFromName('fatchip_computop_amazonpay');

        $this->view->assign('fatchipCTAmazonpayID', $fatchipCTAmazonpayID);
        $this->view->assign('fatchipCTResponse', $params['fatchipCTResponse']);
        $this->view->assign('fatchipCTPaymentConfig', $this->config);
        $this->view->assign('sStepActive', 'paymentShipping');

        // override template with ours for xhr requests
        if ($this->Request()->getParam('isXHR')) {
            return $this->view->loadTemplate('frontend/fatchipCTAmazonCheckout/fatchip_shipping_payment_core.tpl');
        }

        // load Template to avoid annoying uppercase to _lowercase conversion
        $this->view->loadTemplate('frontend/fatchipCTAmazonCheckout/shipping_payment.tpl');
    }

    /**
     * {inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        $returnArray = array(
            'shippingPayment',
        );
        return $returnArray;
    }

    /**
     *  extends confirm with custom template
     *
     * @return void|Enlight_View_Default
     */
    public function confirmAction()
    {
        parent::confirmAction();
        $this->view->loadTemplate('frontend/fatchipCTAmazonCheckout/confirm.tpl');
    }

    /**
     *  extends finish with custom template
     *
     * also unsets all session variables and refreshes basket
     *
     * @throws Exception
     * @return void
     */
    public function finishAction()
    {
        parent::finishAction();
        $this->view->loadTemplate('frontend/fatchipCTAmazonCheckout/finish.tpl');
        Shopware()->Session()->unsetAll();
        Shopware()->Modules()->Basket()->sRefreshBasket();
    }

    /**
     *  get amazon order information from computop api
     *
     * @return \Fatchip\CTPayment\CTResponse $response
     */
    public function ctGetOrderDetails()
    {
        $session = Shopware()->Session();
        # TODO use default orderDesc
        $orderDesc = "Test";

        /** @var \Fatchip\CTPayment\CTPaymentMethods\AmazonPay $payment */
        $payment = $this->paymentService->getPaymentClass('AmazonPay', $this->config);
        $requestParams = $payment->getAmazonGODParams(
            $session->offsetGet('fatchipCTPaymentPayID'),
            $orderDesc,
            $session->offsetGet('fatchipCTAmazonReferenceID')
        );

        $response = $this->plugin->callComputopService($requestParams, $payment, 'GOD', $payment->getCTPaymentURL());
        return $response;
    }
}


