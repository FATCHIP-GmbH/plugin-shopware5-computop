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

namespace Shopware\Plugins\FatchipFCSPayment\Subscribers\Backend;

use Enlight_Hook_HookArgs;
use Enlight\Event\SubscriberInterface;
use Shopware\Models\Order\Detail;
use Shopware\Models\Attribute\OrderDetail as Attribute_OrderDetail;
use RuntimeException;
use Exception;

/**
 * Class BackendOrder
 *
 * @package Shopware\Plugins\FatchipFCSPayment\Subscribers
 */
class OrderList implements SubscriberInterface
{
    /**
     * return array with all subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return ['Shopware_Controllers_Backend_Order::getList::after' => 'order__getList__after'];
    }

    /**
     * Add attribute data to detail-data
     *
     * @param Enlight_Hook_HookArgs $args
     *
     * @throws Exception
     */
    public function order__getList__after(Enlight_Hook_HookArgs $args)
    {
        $return = $args->getReturn();


        if (empty($return['success']) || empty($return['data'])) {
            return;
        }

        foreach ($return['data'] as &$order) {
            foreach ($order['details'] as &$orderDetail) {
                //get detail attribute
                /** @var Detail $detail */
                $detail = Shopware()->Models()->getRepository(Detail::class)->find($orderDetail['id']);

                if ( ! isset($detail)) {
                    continue;
                }

                $attribute = $this->getOrderDetailAttribute($detail);

                //TODO: check brutto/netto
                $orderDetail['fcctcaptured'] = $attribute->getFatchipctCaptured();
                $orderDetail['fcctdebit'] = $attribute->getFatchipctDebit();
            }
            unset($orderDetail);
        }
        unset($order);

        $args->setReturn($return);
    }

    /**
     * Gets the Attribute from $detail or creates them if no
     * attributes exist yet
     *
     * @param Detail $detail
     *
     * @return Attribute_OrderDetail
     *
     * @throws RuntimeException
     */
    private function getOrderDetailAttribute(Detail $detail)
    {
        if ($detail !== null && $attribute = $detail->getAttribute()) {
            return $attribute;
        }

        if ( ! $detail instanceof Detail) {
            throw new RuntimeException('Unknown attribute base class');
        }

        $attribute = new Attribute_OrderDetail();

        $detail->setAttribute($attribute);

        return $attribute;
    }

}
