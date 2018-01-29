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

// add baseclass via require_once so we can extend
// ToDo find a better solution for this
require_once 'FatchipCTPayment.php';


/**
 * Class Shopware_Controllers_Frontend_FatchipCTAmazon
 */
class Shopware_Controllers_Frontend_FatchipCTAmazon extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    private $session;

    /**
     * Init method that get called automatically
     *
     * Set class properties
     */
    public function init()
    {
        $this->session = Shopware()->Session();
    }

    public function loginAction()
    {
        // Debug:
        $request = $this->Request();
        $params = $request->getParams();
        $cookie = $request->getCookie('amazon_Login_accessToken');
        if (!empty($this->Request()->getParam("access_token"))) {
            $this->session->offsetSet('fatchipCTAmazonAccessToken', $this->Request()->getParam("access_token"));
        }

        // this is set in register template in javascript when returning from amazon Login
        if (!empty($this->Request()->getCookie("fatchipCTAmazon_Login_accessToken"))) {
            $this->session->offsetSet('fatchipCTAmazonAccessToken', $this->Request()->getCookie("amazon_Login_accessToken"));
        }
    }

}


