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

namespace Shopware\Plugins\FatchipCTPayment\Subscribers\Logger;

use Monolog\Handler\RotatingFileHandler;

use Enlight\Event\SubscriberInterface;

use Shopware;
use Shopware\Components\Logger;

/**
 * Class Logger
 *
 * @package Shopware\Plugins\FatchipCTPayment\Subscribers
 */
abstract class AbstractLoggerSubscriber implements SubscriberInterface
{
    /**
     * @var $logger \Shopware\Components\Logger
     */
    protected $logger;

    /**
     * FatchipCTPayment Plugin Bootstrap Class
     *
     * @var \Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    protected $plugin;

    /**
     * FatchipCTPayment Configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Logger constructor
     */
    public function __construct()
    {
        $this->plugin = Shopware()->Container()->get('plugins')->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();

        // ToDO use ternary operator here
        // Shopware()->Application() is deprecated
        $logPath = Shopware()->DocPath();
        if (version_compare(Shopware::VERSION, '5.1', '>=')) {
            $logFile = $logPath . 'var/log/FatchipCTPayment_production.log';
        } else {
            $logFile = $logPath . 'logs/FatchipCTPayment_production.log';
        }
        $rfh = new RotatingFileHandler($logFile, 14);
        $this->logger = new Logger('FatchipCTPayment');
        $this->logger->pushHandler($rfh);
    }

    /**
     * Checks if it is a controller for a FatchipCT Payment Method
     *
     * @param $controllerName
     *
     * @return bool
     */
    public function isFatchipCTController($controllerName)
    {
        // strpos returns false or int for position
        return is_int(strpos($controllerName, 'FatchipCT'));
    }
}
