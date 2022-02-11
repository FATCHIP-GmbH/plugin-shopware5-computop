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
use Enlight_Event_EventArgs;
use Shopware\Plugins\FatchipFCSPayment\Subscribers\AbstractSubscriber;

class AfterPay extends AbstractSubscriber
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (position defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     * <code>
     * return array(
     *     'eventName0' => 'callback0',
     *     'eventName1' => array('callback1'),
     *     'eventName2' => array('callback2', 10),
     *     'eventName3' => array(
     *         array('callback3_0', 5),
     *         array('callback3_1'),
     *         array('callback3_2')
     *     )
     * );
     *
     * </code>
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onPostDispatchFrontendCheckout',
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'hidePaymentInList'
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function hidePaymentInList(Enlight_Event_EventArgs $args) {
        $userData = Shopware()->Modules()->Admin()->sGetUserData();

        $payments = $args->getReturn();

        if (!$this->utils->afterpayProductExistsforBasketValue($this->config['merchantID'], $userData, false))
        {
            $payments = $this->utils->hidePayment('fatchip_firstcash_afterpay_installment', $payments);
        }

        if (!empty($userData['billingaddress']['company']))
        {
            $payments = $this->utils->hidePayment('fatchip_firstcash_afterpay_installment', $payments);
            $payments = $this->utils->hidePayment('fatchip_firstcash_afterpay_invoice', $payments);
        }

        $args->setReturn($payments);
    }

    /**
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendCheckout(Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();
        $pluginConfig = Shopware()->Plugins()->Frontend()->FatchipFCSPayment()->Config()->toArray();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if (!$request->isDispatched() or !stristr($paymentName, 'fatchip_firstcash_afterpay')) { // no afterpay payment method
            return;
        }

        if ($request->getActionName() == 'shippingPayment' && $paymentName == 'fatchip_firstcash_afterpay_installment') {
            $view->assign('fatchipFCSPaymentConfig', $pluginConfig);
        }

        // prevent skipping of shippingpayment
        if ($request->getActionName() == 'confirm' && $paymentName == 'fatchip_firstcash_afterpay_installment') {
            $session = Shopware()->Session();
            if (!$session->offsetExists('FatchipFirstCashAfterpayProductNr')) {
                $controller->redirect(
                    array(
                        'controller' => 'checkout',
                        'action' => 'shippingPayment',
                    )
                );
            }
        }
    }
}
