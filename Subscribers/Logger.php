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
 * Class Logger
 *
 * @package Shopware\Plugins\FatchipCTPayment\Subscribers
 */
class Logger implements SubscriberInterface
{
    /**
     * @var $logger \Shopware\Components\Logger
     */
    private $logger;

    /**
     * FatchipCTpayment Plugin Bootstrap Class
     * @var \Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    /**
     * FatchipCTPayment Configuration
     * @var array
     */
    protected $config;

    /**
     * Returns the subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend' =>
                'onPostDispatchFatchipCT',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' =>
                'onPostDispatchSecureFatchipCT',
           'Enlight_Controller_Action_PostDispatch_Backend_Index' =>
             'onPostDispatchBackendIndex',
        ];
    }

    /**
     * Logger constructor
     */
    public function __construct(){

        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();

        // ToDO use terniary operator here
        // Shopware()->Application() is deprecated
        $logPath = Shopware()->DocPath();
        if (version_compare(\Shopware::VERSION, '5.1', '>=')){
            $logFile = $logPath . 'var/log/FatchipCTPayment_production.log';
        } else {
            $logFile = $logPath . 'logs/FatchipCTPayment_production.log';
        }
        $rfh = new \Monolog\Handler\RotatingFileHandler($logFile, 14);
        $this->logger = new \Shopware\Components\Logger('FatchipCTPayment');
        $this->logger->pushHandler($rfh);
    }

    /**
     * Checks if it is a cotnroller for a FatchipCT Payment Method
     * @param $controllerName
     * @return bool
     */
    public function isFatchipCTController($controllerName)
    {
        // strpos returns false or int for position
        return is_int(strpos($controllerName, 'FatchipCT'));
    }

    // Todo check here for any exceptions and log them with stack trace??
    // in addition log json response of our Ajax Controllers
    /**
     * Logs Requestpamaters for FatchipCT controllers if Debuglogging is activated in Pluginsettings
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFatchipCT(\Enlight_Controller_ActionEventArgs $args)
    {
        $request = $args->getRequest();
        if ($this->isFatchipCTController($request->getControllerName()) && $this->config['debuglog'] === 'active'){
            $this->logger->debug("postDispatch:" . $request->getControllerName() . " " . $request->getActionName() . ":");
            $this->logger->debug("RequestParams:", $request->getParams());
        }
    }

    // should only be triggered when no exceptions occured
    /**
     * Logs Requestpamaters and Template information for FatchipCT controllers if Debuglogging is activated in Pluginsettings
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchSecureFatchipCT(\Enlight_Controller_ActionEventArgs $args)
    {
        $subject = $args->getSubject();
        $request = $args->getRequest();

        if ($this->isFatchipCTController($request->getControllerName()) && $this->config['debuglog'] === 'active'){

            $this->logger->debug("postDispatchSecure:" . $request->getControllerName() . " " . $request->getActionName() . ":");
            $this->logger->debug("RequestParams:", $request->getParams());

            if ($subject->View()->hasTemplate()){
                $this->logger->debug("Template Name:", [$subject->View()->Template()->template_resource]);
                $this->logger->debug("Template Vars:", $subject->View()->Engine()->smarty->tpl_vars);
            }
        }
    }
    /**
     * Extends Backend header with CSS to display CT-Icon in Menu
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendIndex(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Index $subject */
        $subject = $args->get('subject');
        $request = $subject->Request();
        $response = $subject->Response();
        $view = $subject->View();

        $view->addTemplateDir(__DIR__ . '/../Views');

        if (!$request->isDispatched() ||
          $response->isException() ||
          $request->getModuleName() != 'backend' ||
          !$view->hasTemplate()) {
            return;
        }
        $view->extendsTemplate('responsive/backend/index/computop.tpl');
    }
}
