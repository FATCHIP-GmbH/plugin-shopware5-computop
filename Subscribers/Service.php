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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */

namespace Shopware\Plugins\FatchipFCSPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Fatchip\CTPayment\CTPaymentService;
use Shopware\Plugins\FatchipFCSPayment\Util;

/**
 * Class Service
 *
 * @package Shopware\Plugins\FatchipFCSPayment\Subscribers
 */
class Service implements SubscriberInterface
{
    /**
     * Returns the subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_InitResource_FatchipFCSPaymentApiClient' =>
                'onInitApiClient',
            'Enlight_Bootstrap_InitResource_FatchipFCSPaymentUtils' =>
                'onInitUtils',
        ];
    }

    /**
     * Initialises the api client with plugin settings
     * @return CTPaymentService
     */
    public function onInitApiClient()
    {
        $plugin = Shopware()->Plugins()->Frontend()->FatchipFCSPayment();
        $config = $plugin->Config()->toArray();
        return new CTPaymentService($config);

    }

    /**
     * initialises util
     * @return Util
     */
    public function onInitUtils()
    {
        return new Util();
    }
}
