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
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;

/**
 * provide paths to custom controllers
 */
class ControllerPath implements SubscriberInterface
{

    /**
     * path to plugin files
     *
     * @var string
     */
    private $path;

    /**
     * inject path to plugin files
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * returns array with all subsribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return array(
            //Frontend
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPayment'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTCreditCard'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTEasyCredit'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTIdeal'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTKlarna'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTKlarnaPayments'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTLastschrift'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTMobilepay'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaydirekt'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaypalStandard'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaypalExpress'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaypalExpressRegister'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaypalExpressCheckout'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPostFinance'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPrzelewy24'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTSofort'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTAmazonRegister'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTAmazonCheckout'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTAjax'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTAmazon'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTAfterpay'
            => 'onGetFrontendControllerPath',
            // Backend
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_FatchipCTIdeal'
            => 'onGetBackendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_FatchipCTOrder'
            => 'onGetBackendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_FatchipCTApilog'
            => 'onGetBackendControllerPath',
        );
    }

    /**
     * Provide path to custom frontend controllers
     * @param \Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetFrontendControllerPath(\Enlight_Event_EventArgs $args)
    {
        $controllerName = $args->getRequest()->getControllerName();
        return $this->path . 'Controllers/Frontend/' . $controllerName . '.php';
    }

    /**
     * * Provide path to custom backend controllers
     * @param \Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetBackendControllerPath(\Enlight_Event_EventArgs $args)
    {
        $controllerName = $args->getRequest()->getControllerName();
        return $this->path . 'Controllers/Backend/' . $controllerName . '.php';
    }

}
