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
 * @package    FatchipCTPayment
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Fatchip\CTPayment\CTPaymentService;
use Shopware\Plugins\FatchipCTPayment\Util;

/**
 * Class Service
 *
 * @package Shopware\Plugins\FatchipCTPayment\Subscribers
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
            'Enlight_Bootstrap_InitResource_FatchipCTPaymentApiClient' =>
                'onInitApiClient',
            'Enlight_Bootstrap_InitResource_FatchipCTPaymentUtils' =>
                'onInitUtils',
        ];
    }

    /**
     * Initialises the api client with plugin settings
     * @return CTPaymentService
     */
    public function onInitApiClient()
    {
        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
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
