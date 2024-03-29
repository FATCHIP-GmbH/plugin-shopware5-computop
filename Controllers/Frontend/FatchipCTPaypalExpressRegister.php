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
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

require_once 'FatchipCTPayment.php';

use Shopware\Plugins\FatchipCTPayment\Util;
use Shopware\Components\CSRFWhitelistAware;
use TheIconic\NameParser\Parser;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTPaypalExpressRegisters
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */
class Shopware_Controllers_Frontend_FatchipCTPaypalExpressRegister extends Shopware_Controllers_Frontend_Register implements CSRFWhitelistAware
{
    /**
     * FatchipCTpayment Plugin Bootstrap Class
     *
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    /**
     * FatchipCTPayment plugin settings
     *
     * @var array
     */
    protected $config;

    /**
     * FatchipCTPaymentUtils
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
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
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

        $session->offsetSet('sPaymentID', $this->utils->getPaymentIdFromName('fatchip_computop_paypal_express'));

        $AddrCountryCodeID = $this->utils->getCountryIdFromIso($params['CTResponse']->getAddrCountryCode());

        $parser = new Parser();
        $shipping = $parser->parse($params['CTResponse']->getName());
        $shippingFirstname = implode(" ", [$shipping->getFirstname(), $shipping->getInitials(), $shipping->getMiddlename()]);
        $shippingLastname = $shipping->getLastname();

        $this->view->assign('fatchipCTResponse', $params['CTResponse']);
        $this->view->assign('fatchipAddrCountryCodeID', $AddrCountryCodeID);
        $this->view->assign('fatchipAddrFirstName', $params['CTResponse']->getFirstName());
        $this->view->assign('fatchipAddrShippingFirstname', $shippingFirstname);
        $this->view->assign('fatchipAddrLastName', $params['CTResponse']->getLastName());
        $this->view->assign('fatchipAddrShippingLastname', $shippingLastname);
        $this->view->assign('fatchipAddrBirthday', $birthday);
        $this->view->assign('fatchipAddrBirthdayDay',$birthdayDay);
        $this->view->assign('fatchipAddrBirthdayMonth',$birthdayMonth);
        $this->view->assign('fatchipAddrBirthdayYear',$birthdayYear);
        $this->view->assign('fatchipAddrPhone',$phone);
        $this->view->assign('fatchipCTPaymentConfig', $this->config);
        $this->view->loadTemplate('frontend/fatchipCTPaypalExpressRegister/index.tpl');
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


