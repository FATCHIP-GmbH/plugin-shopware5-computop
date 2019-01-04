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

namespace Shopware\Plugins\FatchipCTPayment\Subscribers\Backend;

use Enlight_Controller_ActionEventArgs;

use Enlight\Event\SubscriberInterface;

class PostDispatchBackendOrder implements SubscriberInterface
{
    /**
     * return array with all subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        // extend backend order-overview
        return ['Enlight_Controller_Action_PostDispatch_Backend_Order' => 'fatchipCTExtendController_Backend_Order'];
    }

    /**
     * Adds Javascript to order backend
     *
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function fatchipCTExtendController_Backend_Order(Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->extendsTemplate('backend/fcct_order/controller/detail.js');
        $view->extendsTemplate('backend/fcct_order/model/position.js');
        $view->extendsTemplate('backend/fcct_order/view/detail/overview.js');
        $view->extendsTemplate('backend/fcct_order/view/detail/position.js');
    }
}
