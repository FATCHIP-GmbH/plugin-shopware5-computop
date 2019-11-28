<?php
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */

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


use Enlight_Controller_ActionEventArgs;
use Fatchip\CTPayment\CTPaymentMethods\KlarnaPayments as PaymentClass;
use Shopware\Plugins\FatchipCTPayment\Subscribers\AbstractSubscriber;

/**
 * @property PaymentClass $payment
 */
class KlarnaPayments extends AbstractSubscriber
{
    protected $paymentClass = 'KlarnaPayments';

    /**
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => array(
                array('onShippingPayment'),
                array('onPayment'),
                array('onFinish'),
            )
        ];
    }

    public function onShippingPayment(Enlight_Controller_ActionEventArgs $args) {
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();
        $session = Shopware()->Session();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if ($request->getActionName() == 'shippingPayment') {
            $paymentType = $this->utils->getKlarnaPaymentTypeFromPaymentName($paymentName);

            $view->assign('paymentType', $paymentType);
            $view->assign('billingAddressStreetAddress', $userData['billingaddress']['street']);
            $view->assign('billingAddressCity', $userData['billingaddress']['city']);
            $view->assign('billingAddressGivenName', $userData['billingaddress']['firstname']);
            $view->assign('billingAddressPostalCode', $userData['billingaddress']['zipcode']);
            $view->assign('billingAddressFamilyName', $userData['billingaddress']['lastname']);
            $view->assign('billingAddressEmail', $userData['additional']['user']['email']);
            $view->assign('purchaseCurrency', Shopware()->Container()->get('currency')->getShortName());
            $view->assign('locale', str_replace('_', '-', Shopware()->Shop()->getLocale()->getLocale()));
            $view->assign('billingAddressCountry', $userData['additional']['country']['countryiso']);
        }

        if (!stristr($paymentName, 'klarna')) { // no klarna payment method
            return;
        }

        if ($request->getActionName() == 'shippingPayment') {
            $userData = Shopware()->Modules()->Admin()->sGetUserData();
            $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

            if (stristr($paymentName, 'klarna')) {
                if ($ctError = $session->offsetGet('CTError')) {
                    $session->offsetUnset('CTError');
                    $params['CTError'] = $ctError;
                }

                $requestParams = $this->payment->createCTKlarnaPayment();

                if (!$requestParams) {
                    $args->getSubject()->forward('shippingPayment', 'checkout');
                }

                if ($this->payment->needNewKlarnaSession()) {
                    // accessToken does not exist in session, so a new session must be created
                    $CTResponse = $this->payment->requestSession($requestParams);

                    if ($CTResponse->getStatus() === 'FAILED') {
                        $msg = 'Es ist ein Fehler aufgetreten, bitte wählen Sie eine andere Zahlart aus.';
                        $ctError = [
                            'CTErrorMessage' => $msg,
                            'CTErrorCode' => '',
                        ];
                        $params['CTError'] = $ctError;
                    }

                    $articleListBase64 = $requestParams['ArticleList'];
                    $amount = $requestParams['amount'];
                    $addressHash = $this->payment->createAddressHash();
                    $dispatch = $session->offsetGet('sDispatch');

                    $session->offsetSet('FatchipCTKlarnaPaymentArticleListBase64', $articleListBase64);
                    $session->offsetSet('FatchipCTKlarnaPaymentAmount', $amount);
                    $session->offsetSet('FatchipCTKlarnaPaymentAddressHash', $addressHash);
                    $session->offsetSet('FatchipCTKlarnaPaymentDispatchID', $dispatch);

                    $session->offsetSet('FatchipCTKlarnaPaymentSessionResponsePayID', $CTResponse->getPayID());
                    $session->offsetSet('FatchipCTKlarnaPaymentSessionResponseTransID', $CTResponse->getTransID());

                    $accessToken = $CTResponse->getAccesstoken();

                    $session->offsetSet('FatchipCTKlarnaAccessToken', $accessToken);
                }
            }

            $view->assign('CTError', $params['CTError']);
        }
    }

    public function onPayment(Enlight_Controller_ActionEventArgs $args) {
        $controller = $args->getSubject();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if (!stristr($paymentName, 'klarna')) { // no klarna payment method
            return;
        }

        if ($controller->Request()->getActionName() === 'payment') {
            /** @var PaymentClass $payment */

            $errMsg = 'Durch die Nachträgliche Änderung, muss die Zahlart neu ausgewählt werden. Bitte wählen Sie erneut
            eine Zahlart aus. Durch Klick auf "Weiter" kann auch die aktuell ausgewählte Zahlart genutzt werden.';

            if ($this->payment->needNewKlarnaSession()) {
                $this->utils->redirectToShippingPayment($controller, $errMsg);

                return;
            }
        }
    }

    public function onFinish(Enlight_Controller_ActionEventArgs $args)
    {
        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if (!stristr($paymentName, 'klarna')) { // no klarna payment method
            return;
        }

        //clear klarna session variables on finish
        if ($args->getSubject()->Request()->getActionName() === 'finish') {
            $this->payment->cleanSessionVars();

            $this->utils->selectDefaultPayment();
        }
    }
}
