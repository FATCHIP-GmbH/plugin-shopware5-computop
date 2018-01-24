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

namespace Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\FatchipCTPayment\Util;

class Checkout implements SubscriberInterface
{

    /** @var Util $utils **/
    protected $utils;

    /**
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
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        $subject = $args->getSubject();
        $request = $subject->Request();
        $params = $request->getParams();
        $session = Shopware()->Session();
        $paymentName = $this->utils->getPaymentNameFromId($params['payment']);
        $test = $request->getActionName();

        // save birthday
        // ToDo prevent forward to checkout confirm if params are missing
        // dont know where to do this (yet)
        if (!empty($params['FatchipComputopPaymentData']) && $request->getActionName() === 'saveShippingPayment' && $paymentName === 'fatchip_computop_easycredit') {
            $this->utils->updateUserDoB($session->get('sUserId'),
                $params['FatchipComputopPaymentData']['fatchip_computop_easycredit_birthyear'].'-'.
                $params['FatchipComputopPaymentData']['fatchip_computop_easycredit_birthmonth'].'-'.
                $params['FatchipComputopPaymentData']['fatchip_computop_easycredit_birthday']
                );
        }

        // ToDo should check here all Session vars?
        if ($paymentName === 'fatchip_computop_easycredit' && $request->getActionName() === 'saveShippingPayment' && !$session->offsetExists('fatchipComputopEasyCreditPayId')) {
            $subject->redirect(['controller' => 'FatchipCTEasyCredit','action' => 'gateway', 'forceSecure' => true]);
        }

    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     * @return void
     */
    public function onPostdispatchFrontendCheckout(\Enlight_Controller_ActionEventArgs $args)
    {
        $subject = $args->getSubject();
        $view = $subject->View();
        $request = $subject->Request();
        $response = $subject->Response();
        $session = Shopware()->Session();
        $params = $request->getParams();


        if (!$request->isDispatched() || $response->isException()) {
            return;
        }

        if ($request->getActionName() == 'shippingPayment') {
            $userData = Shopware()->Modules()->Admin()->sGetUserData();

            $birthday = explode('-', $userData['additional']['user']['birthday']);
            $data['birthday'] = $birthday[2];
            $data['birthmonth'] = $birthday[1];
            $data['birthyear'] = $birthday[0];
            $view->assign('data', $data);

            // assign payment errors and error template to view
            $view->extendsTemplate('frontend/checkout/shipping_payment.tpl');
            // ToDo DO not Display User canceled:22730703 at least for paydirekt
            // logic shouldnt be here or in the template ...
            $view->assign('CTError', $params['CTError']);
        }

        if ($request->getActionName() == 'confirm') {

            $view->extendsTemplate('frontend/checkout/confirm.tpl');

            // add easyCredit Information to view
            if ($session->offsetGet('FatchipComputopEasyCreditInformation')) {
                $view->assign('FatchipComputopEasyCreditInformation', $session->offsetGet('FatchipComputopEasyCreditInformation'));
            }
        }
    }
}