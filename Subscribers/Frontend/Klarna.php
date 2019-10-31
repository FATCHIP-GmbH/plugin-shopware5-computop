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
use Shopware\Plugins\FatchipCTPayment\Subscribers\AbstractSubscriber;
use Shopware\Plugins\FatchipCTPayment\Util;

class Klarna extends AbstractSubscriber
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
        ];
    }

    /**
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendCheckout(Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $utils = Shopware()->Container()->get('FatchipCTPaymentUtils');

        //clear klarna session variables on finish
        if ($args->getSubject()->Request()->getActionName() === 'finish') {
            $utils->cleanSessionVars();

            $utils->selectDefaultPayment();
        }

        if ($controller->Request()->getActionName() !== 'payment') {
            return;
        }

        /** @var Util $utils */

        $userData = Shopware()->Modules()->Admin()->sGetUserData();

        $paymentName = $utils->getPaymentNameFromId($userData['additional']['payment']['id']);
        if (!stristr($paymentName, 'klarna')) { // no klarna payment method
            return;
        }

        /** @var KlarnaPayments $payment */
        $payment = $utils->createCTKlarnaPayment();

        $errMsg = 'Durch die Nachträgliche Änderung, muss die Zahlart neu ausgewählt werden. Bitte wählen Sie erneut
            eine Zahlart aus. Durch Klick auf "Weiter" kann auch die aktuell ausgewählte Zahlart genutzt werden.';

        if ($payment->needNewKlarnaSession()) {
            $utils->redirectToShippingPayment($controller, $errMsg);

            return;
        }
    }
}
