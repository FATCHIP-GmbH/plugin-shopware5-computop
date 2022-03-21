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

require_once 'FatchipFCSPayment.php';

use Shopware\Plugins\FatchipFCSPayment\Util;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSPaypalExpressCheckout.
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */
class Shopware_Controllers_Frontend_FatchipFCSPaypalExpressCheckout extends Shopware_Controllers_Frontend_Checkout implements CSRFWhitelistAware
{
    /**
     * Fatchip PaymentService
     *
     * @var \Fatchip\FCSPayment\CTPaymentService $service
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
        if (method_exists('Shopware_Controllers_Frontend_Checkout', 'init')) {
            parent::init();
        }
        // ToDo handle possible Exception
        $this->paymentService = Shopware()->Container()->get('FatchipFCSPaymentApiClient');
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipFCSPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipFCSPaymentUtils');
    }

    /**
     * Action to handle selection of shipping and payment methods
     *
     * @return Enlight_View_Default
     */
    public function shippingPaymentAction()
    {
        parent::shippingPaymentAction();
        $request = $this->Request();
        $params = $request->getParams();
        $session = Shopware()->Session();
        $fatchipFCSPaypalExpressID = $this->utils->getPaymentIdFromName('fatchip_firstcash_paypal_express');
        $session->offsetSet('sPaymentID', $fatchipFCSPaypalExpressID);

        $this->view->assign('fatchipFCSPaypalExpressID', $fatchipFCSPaypalExpressID);
        $this->view->assign('fatchipFCSResponse', $params['fatchipFCSResponse']);
        $this->view->assign('fatchipFCSPaymentConfig', $this->config);
        $this->view->assign('sStepActive', 'paymentShipping');

        // override template with ours for xhr requests
        if ($this->Request()->getParam('isXHR')) {
            return $this->view->loadTemplate('frontend/fatchipFCSPaypalExpressCheckout/fatchip_shipping_payment_core.tpl');
        }
        $this->view->loadTemplate('frontend/fatchipFCSPaypalExpressCheckout/shipping_payment.tpl');
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return void
     */
    public function confirmAction()
    {
        parent::confirmAction();
        $this->view->loadTemplate('frontend/fatchipFCSPaypalExpressCheckout/confirm.tpl');
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     * @throws Exception
     */
    public function finishAction()
    {
        parent::finishAction();

        $this->view->loadTemplate('frontend/fatchipFCSPaypalExpressCheckout/finish.tpl');
        Shopware()->Session()->unsetAll();
        Shopware()->Modules()->Basket()->sRefreshBasket();
    }
}



