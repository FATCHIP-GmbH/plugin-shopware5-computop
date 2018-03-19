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

namespace Shopware\Plugins\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\Order\Detail;
use Shopware\Models\Attribute\OrderDetail;

class BackendOrder implements SubscriberInterface
{

    /**
     * di container
     *
     * @var Container
     */
    private $container;

    /**
     * inject di container
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            // extend backend order-overview
          'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'fatchipCTExtendController_Backend_Order',
            //add payone fields to list results
          'Shopware_Controllers_Backend_Order::getList::after' => 'Order__getList__after',
        );
    }

    public function fatchipCTExtendController_Backend_Order(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->extendsTemplate('backend/fcct_order/controller/detail.js');
        $view->extendsTemplate('backend/fcct_order/model/position.js');
        $view->extendsTemplate('backend/fcct_order/view/detail/overview.js');
        $view->extendsTemplate('backend/fcct_order/view/detail/position.js');
    }

    /**
     * add attribute data to detail-data
     * @parent fnc head: protected function getList($filter, $sort, $offset, $limit)
     *
     * @param \Enlight_Hook_HookArgs  $args
     */
    public function Order__getList__after(\Enlight_Hook_HookArgs  $args)
    {
        $return = $args->getReturn();


        if (empty($return['success']) || empty($return['data'])) {
            return;
        }

        foreach ($return['data'] as &$order) {
            foreach ($order["details"] as &$orderDetail) {
                //get detail attribute
                $detailObj                         = Shopware()->Models()->getRepository('Shopware\Models\Order\Detail')
                  ->find($orderDetail['id']);
                $attribute                         = $this->getOrderDetailAttributes($detailObj);

                //TODO: check brutto/netto
                $orderDetail['fcctcaptured'] = $attribute->getfatchipctCaptured();
                $orderDetail['fcctdebit']    = $attribute->getfatchipctDebit();

            }
        }

        $args->setReturn($return);
    }

    private function getOrderDetailAttributes($detailObject) {
        if (!empty($detailObject) && $attribute = $detailObject->getAttribute()) {
            return $attribute;
        }
        if ($detailObject instanceof Detail) {
            if (!$attribute = Shopware()->Models()->getRepository('Shopware\Models\Attribute\OrderDetail')
              ->findOneBy(array('orderDetailId' => $detailObject->getId()))) {
                $attribute = new OrderDetail();
            }
        } else {
            throw new \Exception('Unknown attribute base class');
        }
        $detailObject->setAttribute($attribute);
        return $attribute;
    }

}
