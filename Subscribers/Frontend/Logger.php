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
 * @link       https://www.firstcashsolution.de/
 */

namespace Shopware\Plugins\FatchipFCSPayment\Subscribers\Frontend;

use Enlight_Controller_ActionEventArgs;
use Enlight_Exception;
use Monolog\Handler\RotatingFileHandler;
use Shopware\Plugins\FatchipFCSPayment\Subscribers\AbstractSubscriber;
use Shopware\Plugins\FatchipFCSPayment\Util;

/**
 * Class Logger
 *
 * @package Shopware\Plugins\FatchipFCSPayment\Subscribers
 */
class Logger extends AbstractSubscriber
{
    /**
     * @var $logger \Shopware\Components\Logger
     */
    protected $logger;

    /**
     * FatchipFCSPayment Plugin Bootstrap Class
     *
     * @var \Shopware_Plugins_Frontend_FatchipFCSPayment_Bootstrap
     */
    protected $plugin;

    /**
     * FatchipFCSPayment Configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Logger constructor
     */
    public function __construct()
    {
        $this->plugin = Shopware()->Container()->get('plugins')->Frontend()->FatchipFCSPayment();
        $this->config = $this->plugin->Config()->toArray();

        // ToDO use ternary operator here
        // Shopware()->Application() is deprecated
        $logPath = Shopware()->DocPath();

        if (Util::isShopwareVersionGreaterThanOrEqual('5.1')) {
            $logFile = $logPath . 'var/log/FatchipFCSPayment_production.log';
        } else {
            $logFile = $logPath . 'logs/FatchipFCSPayment_production.log';
        }
        $rfh = new RotatingFileHandler($logFile, 14);
        $this->logger = new \Shopware\Components\Logger('FatchipFCSPayment');
        $this->logger->pushHandler($rfh);
    }

    /**
     * Returns the subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend' => 'onPostDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' => 'onPostDispatchSecure'
        ];
    }

    // Todo check here for any exceptions and log them with stack trace??
    // in addition log json response of our Ajax Controllers
    /**
     * Logs request parameters for FatchipFCS controllers if debug logging is activated in plugin settings
     *
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        $request = $args->getRequest();

        if ($this->config['debuglog'] === 'active' && $this->isFatchipFCSController($request->getControllerName())) {
            $this->logger->debug(
                'postDispatch: ' . $request->getControllerName() . ' ' . $request->getActionName() . ':'
            );
            $this->logger->debug('RequestParams: ', $request->getParams());
        }
    }

    /**
     * Logs request parameters and Template information for FatchipFCS controllers if debug logging is activated in
     * plugin settings
     *
     * @param Enlight_Controller_ActionEventArgs $args
     *
     * @throws Enlight_Exception
     */
    public function onPostDispatchSecure(Enlight_Controller_ActionEventArgs $args)
    {
        $subject = $args->getSubject();
        $request = $args->getRequest();

        if ($this->config['debuglog'] === 'active' && $this->isFatchipFCSController($request->getControllerName())) {
            $this->logger->debug(
                'postDispatchSecure: ' . $request->getControllerName() . ' ' . $request->getActionName() . ':'
            );
            $this->logger->debug('RequestParams: ', $request->getParams());

            if ($subject->View()->hasTemplate()) {
                $this->logger->debug('Template Name:', [$subject->View()->Template()->template_resource]);
                $this->logger->debug('Template Vars:', $subject->View()->Engine()->smarty->tpl_vars);
            }
        }
    }

    /**
     * Checks if it is a controller for a FatchipFCS Payment Method
     *
     * @param $controllerName
     *
     * @return bool
     */
    public function isFatchipFCSController($controllerName)
    {
        // strpos returns false or int for position
        return is_int(strpos($controllerName, 'FatchipFCS'));
    }
}
