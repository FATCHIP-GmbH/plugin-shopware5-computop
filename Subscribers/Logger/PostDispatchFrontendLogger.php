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

use Enlight_Controller_ActionEventArgs;

/**
 * Class Logger
 *
 * @package Shopware\Plugins\FatchipCTPayment\Subscribers
 */
class PostDispatchFrontendLogger extends AbstractLoggerSubscriber
{
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
        ];
    }

    // Todo check here for any exceptions and log them with stack trace??
    // in addition log json response of our Ajax Controllers
    /**
     * Logs Requestpamaters for FatchipCT controllers if Debuglogging is activated in Pluginsettings
     *
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFatchipCT(
        Enlight_Controller_ActionEventArgs $args
    ) {
        $request = $args->getRequest();

        if (
            $this->config['debuglog'] === 'active'
            && $this->isFatchipCTController($request->getControllerName())
        ) {
            $this->logger->debug(
                'postDispatch: '
                . $request->getControllerName() . ' '
                . $request->getActionName() . ':'
            );
            $this->logger->debug('RequestParams: ', $request->getParams());
        }
    }
}
