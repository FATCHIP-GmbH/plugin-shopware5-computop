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

namespace Shopware\Plugins\FatchipCTPayment\Subscribers\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_Controller_ActionEventArgs;
use Exception;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTPaymentMethods\KlarnaPayments;
use Shopware\Plugins\FatchipCTPayment\Util;

class CheckoutPayment implements SubscriberInterface
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
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onPostDispatchFrontendCheckoutPayment',
        ];
    }

    /**
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendCheckoutPayment(Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();

        if ($controller->Request()->getActionName() !== 'payment') {
            return;
        }

        /** @var Util $utils */
        $utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        $userData = Shopware()->Modules()->Admin()->sGetUserData();

        $paymentName = $utils->getPaymentNameFromId($userData['additional']['payment']['id']);
        if (!stristr($paymentName, 'klarna')) {
            return;
        }

        /** @var CTOrder $ctOrder */
        $ctOrder = $utils->createCTOrder();
        /** @var KlarnaPayments $payment */
        $payment = $utils->createCTKlarnaPayment();
        $session = Shopware()->Session();

        $sessionAmount = $session->get('FatchipCTKlarnaPaymentAmount', '');
        $currentAmount = $ctOrder->getAmount();
        if ($currentAmount > $sessionAmount) {
            $this->redirectToShippingPayment($controller);

            return;
        }

        $sessionArticleList = $session->get('FatchipCTKlarnaPaymentArticleList', '');
        $currentArticleList = $payment->createArticleList();
        if ($sessionArticleList !== $currentArticleList) {
            $this->callUpdateArticleList($ctOrder, $payment);
        }
    }

    /**
     * @param CTOrder $ctOrder
     * @param KlarnaPayments $payment
     */
    public function callUpdateArticleList($ctOrder, $payment)
    {
        $session = Shopware()->Session();
        $articleList = $payment->createArticleList();
        $currentAmount = $ctOrder->getAmount();

        $payId = $session->offsetGet('FatchipCTKlarnaPaymentSessionResponsePayID');
        $transId = $session->offsetGet('FatchipCTKlarnaPaymentSessionResponseTransID');
        $currency = $ctOrder->getCurrency();
        $eventToken = 'UEO';

        $payment->storeKlarnaUpdateArtikelListRequestParams(
            $payId,
            $transId,
            $currentAmount,
            $currency,
            $eventToken,
            $articleList
        );

        $payment->requestKlarnaUpdateArticleList();
    }

    /**2
     * @param Enlight_Controller_Action $controller
     */
    public function redirectToShippingPayment($controller)
    {
        // redirect to shipping payment with error message
        $session = Shopware()->Session();

        $ctError = [];
        $ctError['CTErrorMessage'] = 'Durch die Nachträgliche Änderung des Warenkorbes ist der neue Endbetrag größer
             als bei der ursprünglichen Zahlartauswahl. Bitte wählen Sie erneut eine Zahlart aus. Durch Klick auf
              "Weiter" kann auch die aktuell ausgewählte Zahlart genutzt werden.';
        $ctError['CTErrorCode'] = ''; //$response->getCode();

        $session->offsetSet('CTError', $ctError);

        try {
            $controller->redirect([
                'action' => 'shippingPayment',
                'controller' => 'checkout'
            ]);
        } catch (Exception $e) {
            // TODO: log
        }
    }
}
