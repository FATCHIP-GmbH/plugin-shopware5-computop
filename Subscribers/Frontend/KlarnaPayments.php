<?php
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */

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
use Fatchip\CTPayment\CTPaymentMethods\KlarnaPayments as PaymentClass;
use Shopware\Plugins\FatchipFCSPayment\Subscribers\AbstractSubscriber;

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
                array('onShippingPaymentDispatched'),
                array('onPayment'),
                array('onFinish'),
            )
        ];
    }

    public function onShippingPayment(Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();

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
    }

    public function onShippingPaymentDispatched(Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();
        $session = Shopware()->Session();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if (!$request->isDispatched() or !stristr($paymentName, 'fatchip_firstcash_klarna_')) { // no klarna payment method
            return;
        }

        if ($request->getActionName() == 'shippingPayment') {
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
                    $msg = Shopware()->Snippets()
                        ->getNamespace('frontend/FatchipFCSPayment/translations')
                        ->get('errorGeneral');
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

                $session->offsetSet('FatchipFCSKlarnaPaymentArticleListBase64', $articleListBase64);
                $session->offsetSet('FatchipFCSKlarnaPaymentAmount', $amount);
                $session->offsetSet('FatchipFCSKlarnaPaymentAddressHash', $addressHash);
                $session->offsetSet('FatchipFCSKlarnaPaymentDispatchID', $dispatch);

                $session->offsetSet('FatchipFCSKlarnaPaymentSessionResponsePayID', $CTResponse->getPayID());
                $session->offsetSet('FatchipFCSKlarnaPaymentSessionResponseTransID', $CTResponse->getTransID());

                $accessToken = $CTResponse->getAccesstoken();

                $session->offsetSet('FatchipFCSKlarnaAccessToken', $accessToken);
            }

            $view->assign('CTError', $params['CTError']);
        }
    }

    public function onPayment(Enlight_Controller_ActionEventArgs $args) {
        $controller = $args->getSubject();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if (!stristr($paymentName, 'fatchip_firstcash_klarna_')) { // no klarna payment method
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

        if (!stristr($paymentName, 'fatchip_firstcash_klarna_')) { // no klarna payment method
            return;
        }

        //clear klarna session variables on finish
        if ($args->getSubject()->Request()->getActionName() === 'finish') {
            $this->payment->cleanSessionVars();

            $this->utils->selectDefaultPayment();
        }
    }
}
