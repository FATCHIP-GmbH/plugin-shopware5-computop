<?php
namespace Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;

class BackendOrder implements SubscriberInterface
{

    /**
     * di container
     *
     * @var \Shopware\Components\DependencyInjection\Container
     */
    private $container;

    /**
     * inject di container
     *
     * @param \Shopware\Components\DependencyInjection\Container $container
     */
    public function __construct(\Shopware\Components\DependencyInjection\Container $container)
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
        if ($detailObject instanceof Shopware\Models\Order\Detail) {
            if (!$attribute = Shopware()->Models()->getRepository('Shopware\Models\Attribute\OrderDetail')
              ->findOneBy(array('orderDetailId' => $detailObject->getId()))) {
                $attribute = new Shopware\Models\Attribute\OrderDetail();
            }
        } else {
            throw new Exception('Unknown attribute base class');
        }
        $detailObject->setAttribute($attribute);
        return $attribute;
    }

}
