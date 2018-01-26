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

namespace Shopware\FatchipCTPayment\Subscribers;

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
     * return array with all subsribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        
        return array(
            //Frontend
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPayment'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTCreditCard'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTEasyCredit'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTIdeal'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTKlarna'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTLastschrift'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTMobilepay'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaydirekt'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaypalStandard'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPostFinance'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPrzelewy24'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTSofort'
            => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_FatchipCTIdeal'
            => 'onGetBackendControllerPath',


        );
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPath(\Enlight_Event_EventArgs $args)
    {
        $controllerName = $args->getRequest()->getControllerName();
        return $this->path . 'Controllers/Frontend/' . $controllerName . '.php';
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetBackendControllerPath(\Enlight_Event_EventArgs $args)
    {
        $controllerName = $args->getRequest()->getControllerName();
        return $this->path . 'Controllers/Backend/' . $controllerName . '.php';
    }

}
