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

require_once 'FatchipCTPayment.php';

/**
 * Class Shopware_Controllers_Frontend_FatchipCTPaypalExpressCheckout
 */
class Shopware_Controllers_Frontend_FatchipCTPaypalExpressCheckout extends Shopware_Controllers_Frontend_Checkout implements CSRFWhitelistAware
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
        if (method_exists(parent::init())){
            parent::init();
        }
        // ToDo handle possible Exception
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
    }


    public function shippingPaymentAction()
    {
        parent::shippingPaymentAction();
        // Debug:
        $request = $this->Request();
        $params = $request->getParams();
        $session = Shopware()->Session();
        $post = $request->getPost();
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $fatchipCTPaypalExpressID = $this->utils->getPaymentIdFromName('fatchip_computop_paypal_express');
        $session->offsetSet('sPaymentID', $fatchipCTPaypalExpressID);

        $this->view->assign('fatchipCTPaypalExpressID', $fatchipCTPaypalExpressID);
        $this->view->assign('fatchipCTResponse', $params['fatchipCTResponse']);
        $this->view->assign('fatchipCTPaymentConfig', $this->config);
        // ToDO check why active step has to be reassigned
        $this->view->assign('sStepActive', 'paymentShipping');

        // override template with ours for xhr requests
        if ($this->Request()->getParam('isXHR')) {
            return $this->view->loadTemplate('frontend/fatchipCTPaypalExpressCheckout/fatchip_shipping_payment_core.tpl');
        }

        // load Template to avoid annoying uppercase to _lowercase conversion
        $this->view->loadTemplate('frontend/fatchipCTPaypalExpressCheckout/shipping_payment.tpl');
    }

    public function getWhitelistedCSRFActions()
    {
        $returnArray = array(
            'shippingPayment',
        );
        return $returnArray;
    }

    public function confirmAction()
    {
        parent::confirmAction();
        $this->view->loadTemplate('frontend/fatchipCTPaypalExpressCheckout/confirm.tpl');
    }

    public function finishAction()
    {
        parent::finishAction();

        $this->view->loadTemplate('frontend/fatchipCTPaypalExpressCheckout/finish.tpl');
        Shopware()->Session()->unsetAll();
        Shopware()->Modules()->Basket()->sRefreshBasket();
    }

}


