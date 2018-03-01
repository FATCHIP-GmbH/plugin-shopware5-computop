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
 * Class Shopware_Controllers_Frontend_FatchipCTPaypalExpressRegisters
 */
class Shopware_Controllers_Frontend_FatchipCTPaypalExpressRegister extends Shopware_Controllers_Frontend_Register implements CSRFWhitelistAware
{
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

        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
    }


    /* make sure this Action can NOT be abused
       for user Registration
    */
    public function registerAction()
    {
        $request = $this->Request();
        $params = $request->getParams();
        $session= Shopware()->Session();
        $session->offsetSet('sPaymentID', $this->utils->getPaymentIdFromName('fatchip_computop_paypal_express'));
        $AddrCountryCodeID = $this->utils->getCountryIdFromIso($params['CTResponse']->getAddrCountryCode());
        $splitNames = explode(' ', $params['CTResponse']->getName());
        // we simply dont use middle names
        // find a nice solution ..
        $lastName = end($splitNames);
        $firstName = $splitNames[0];
        $this->view->assign('fatchipCTResponse', $params['CTResponse']);
        $this->view->assign('fatchipAddrCountryCodeID', $AddrCountryCodeID);
        $this->view->assign('fatchipAddrFirstName', $params['CTResponse']->getFirstName());
        $this->view->assign('fatchipAddrLastName',  $params['CTResponse']->getLastName());
        // add a config->toView method which removed sensitive data from view
        $this->view->assign('fatchipCTPaymentConfig', $this->config);
        // load Template to avoid annoying uppercase to _lowercase conversion
        $this->view->loadTemplate('frontend/fatchipCTPaypalExpressRegister/index.tpl');

    }

    public function getWhitelistedCSRFActions()
    {
        $returnArray = array(
            'saveRegister',
        );
        return $returnArray;
    }
}


