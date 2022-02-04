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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */

namespace Shopware\Plugins\FatchipFCSPayment\Subscribers;

/**
 * provide paths to custom controllers
 */
class ControllerPath extends AbstractSubscriber
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
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSPayment'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSCreditCard'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSEasyCredit'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSIdeal'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSKlarna'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSKlarnaPayments'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSLastschrift'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSPaydirekt'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSPaypalStandard'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSPaypalExpress'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSPaypalExpressRegister'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSPaypalExpressCheckout'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSPostFinance'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSPrzelewy24'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSSofort'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSAmazonRegister'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSAmazonCheckout'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSAjax'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSAmazon'
            => 'onGetFrontendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipFCSAfterpay'
            => 'onGetFrontendControllerPath',
            // Backend
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_FatchipFCSIdeal'
            => 'onGetBackendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_FatchipFCSOrder'
            => 'onGetBackendControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_FatchipFCSApilog'
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
