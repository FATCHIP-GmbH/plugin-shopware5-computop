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

class Checkout implements SubscriberInterface
{

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
        $request = $args->getSubject()->Request();
        $params = $request->getParams();

        // save birthday
        // ToDo prevent forward to checkotu confirm if params are missing
        // dont know where to do this (yet)
        if (!empty($params['FatchipComputopPaymentData'])) {
            $this->saveUserBirthday($params['FatchipComputopPaymentData']);
        }
    }


    /**
     * ToDo refactor , this is only for SW 5.2 yet
     * @param array $paymentData
     */
    private function saveUserBirthday(array $paymentData)
    {
        $session = Shopware()->Session();
        $userId = $session->get('sUserId');
        /* @var \Shopware\Models\Customer\Customer $user */
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);
        $user->setBirthday(
            $paymentData['fatchip_computop_easycredit_birthyear'] . '-' .
            $paymentData['fatchip_computop_easycredit_birthmonth'] . '-' .
            $paymentData['fatchip_computop_easycredit_birthday']
        );
        Shopware()->Models()->persist($user);
        Shopware()->Models()->flush();
    }

    public function onPostdispatchFrontendCheckout(\Enlight_Controller_ActionEventArgs $args)
    {
        $subject = $args->getSubject();
        $view = $subject->View();
        $request = $subject->Request();
        $response = $subject->Response();
        $session = Shopware()->Session();

        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend') {
            return;
        }

        $session = Shopware()->Session();
        $userData = Shopware()->Modules()->Admin()->sGetUserData();

        // ToDo should check here all Session vars?
        if ($request->getActionName() === 'confirm' && !$session->offsetExists('fatchipComputopEasyCreditPayId')) {
            $subject->forward('gatewayEasycredit', 'FatchipCTPayment', null, ['sTarget' => 'checkout']);
        }
    }

}