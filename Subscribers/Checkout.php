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
        $subject = $args->getSubject();
        $request = $subject->Request();
        $params = $request->getParams();
        $session = Shopware()->Session();
        $test = $request->getActionName();

        // save birthday
        // ToDo prevent forward to checkotu confirm if params are missing
        // dont know where to do this (yet)
        if (!empty($params['FatchipComputopPaymentData']) && $request->getActionName() === 'saveShippingPayment' ) {
            $this->saveUserBirthday($params['FatchipComputopPaymentData']);
        }


        $paymentName = $this->getPaymentNameFromId($params['payment']);

        // ToDo should check here all Session vars?
        if ($paymentName === 'fatchip_computop_easycredit' && $request->getActionName() === 'saveShippingPayment' && !$session->offsetExists('fatchipComputopEasyCreditPayId')) {
            $subject->redirect(['controller' => 'FatchipCTEasyCredit','action' => 'gateway', 'forceSecure' => true]);
        }

    }


    /**
     * ToDo refactor , this is only for SW 5.2 yet
     * @param array $paymentData
     * @return void
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

    /**
     * returns payment name
     *
     * @param string $paymentID
     * @return string
     */
    public function getPaymentNameFromId($paymentID)
    {
        $sql         = 'SELECT `name` FROM `s_core_paymentmeans` WHERE id = ?';
        return  Shopware()->Db()->fetchOne($sql, $paymentID);
    }

}