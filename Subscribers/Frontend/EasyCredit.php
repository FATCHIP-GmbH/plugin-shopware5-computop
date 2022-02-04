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
 * @link       https://www.firstcash.com
 */

namespace Shopware\Plugins\FatchipFCSPayment\Subscribers\Frontend;

use Shopware\Plugins\FatchipFCSPayment\Subscribers\AbstractSubscriber;

/**
 * Class Checkout
 *
 * @package Shopware\Plugins\FatchipFCSPayment\Subscribers
 */
class EasyCredit extends AbstractSubscriber
{
    /**
     * return array with all subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Shopware_Controllers_Frontend_Checkout::saveShippingPaymentAction::after' => 'onAfterPaymentAction',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onPostdispatchFrontendCheckout',
        );
    }

    /**
     * Checks the request for computop parameters and validates them.
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onAfterPaymentAction(\Enlight_Hook_HookArgs $args)
    {
        $subject = $args->getSubject();
        $request = $subject->Request();
        $userData = Shopware()->Modules()->Admin()->sGetUserData();

        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if ($request->getActionName() === 'saveShippingPayment') {
            if ($paymentName === 'fatchip_firstcash_easycredit') {
                $subject->redirect(['controller' => 'FatchipFCSEasyCredit', 'action' => 'gateway', 'forceSecure' => true]);
            }
        }
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendCheckout(\Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();
        $session = Shopware()->Session();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if (!$request->isDispatched() or !stristr($paymentName, 'easycredit')) { // no easycredit payment method
            return;
        }

        if ($request->getActionName() == 'confirm') {

            $view->extendsTemplate('frontend/checkout/easycredit_confirm.tpl');

            // add easyCredit Information to view
            if ($session->offsetGet('FatchipFirstCashEasyCreditInformation')) {
                $view->assign('FatchipFirstCashEasyCreditInformation', $session->offsetGet('FatchipFirstCashEasyCreditInformation'));
            }
        }
    }

}
