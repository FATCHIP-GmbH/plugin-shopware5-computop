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
 * Class Shopware_Controllers_Frontend_FatchipFCSPaypalExpressRegisters
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */
class Shopware_Controllers_Frontend_FatchipFCSPaypalExpressRegister extends Shopware_Controllers_Frontend_Register implements CSRFWhitelistAware
{
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
     * Init method does not exist in SW >5.2!
     * Also session property was removed in SW5.2?
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {
        if (method_exists('Shopware_Controllers_Frontend_Register', 'init')) {
            parent::init();
        }
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipFCSPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipFCSPaymentUtils');
    }

    public function indexAction()
    {
        $this->forward('Index', 'Register');
    }

    /**
     * Registers users in shopware.
     *
     * Assigns all neccessary values to view
     * Registration is handled by a jquery plugin
     *
     * @return void
     */
    public function registerAction()
    {
        $request = $this->Request();
        $params = $request->getParams();
        $session= Shopware()->Session();
        $isPhoneMandatory = Shopware()->Config()->get('requirePhoneField');
        $isBirthdayMandatory = Shopware()->Config()->get('requireBirthdayField');
        // $params['CTResponse']->setBirthday('1977-12-19');
        // $params['CTResponse']->setBirthday('1600-01-01');
        $birthday = (empty($params['CTResponse']->getBirthday()) && $isBirthdayMandatory) ? '1910-01-01' : $params['CTResponse']->getBirthday() ;
        $aBirthday = explode("-", $birthday);
        $birthdayDay = (int)$aBirthday[2];
        $birthdayMonth = (int)$aBirthday[1];
        $birthdayYear = (int)$aBirthday[0];
        $phone = (empty($params['CTResponse']->getPhonenumber()) && $isPhoneMandatory) ? '0800 123456789' : $params['CTResponse']->getPhonenumber() ;

        $session->offsetSet('sPaymentID', $this->utils->getPaymentIdFromName('fatchip_firstcash_paypal_express'));

        $AddrCountryCodeID = $this->utils->getCountryIdFromIso($params['CTResponse']->getAddrCountryCode());

        $this->view->assign('fatchipFCSResponse', $params['CTResponse']);
        $this->view->assign('fatchipAddrCountryCodeID', $AddrCountryCodeID);
        $this->view->assign('fatchipAddrFirstName', $params['CTResponse']->getFirstName());
        $this->view->assign('fatchipAddrLastName', $params['CTResponse']->getLastName());
        $this->view->assign('fatchipAddrBirthday', $birthday);
        $this->view->assign('fatchipAddrBirthdayDay',$birthdayDay);
        $this->view->assign('fatchipAddrBirthdayMonth',$birthdayMonth);
        $this->view->assign('fatchipAddrBirthdayYear',$birthdayYear);
        $this->view->assign('fatchipAddrPhone',$phone);
        $this->view->assign('fatchipFCSPaymentConfig', $this->config);
        $this->view->loadTemplate('frontend/fatchipFCSPaypalExpressRegister/index.tpl');
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


