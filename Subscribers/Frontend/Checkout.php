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

use Shopware\Plugins\FatchipCTPayment\Subscribers\AbstractSubscriber;
use Shopware\Plugins\FatchipCTPayment\Util;
use Fatchip\CTPayment\CTOrder\CTOrder;

/**
 * Class Checkout
 *
 * @package Shopware\Plugins\FatchipCTPayment\Subscribers
 */
class Checkout extends AbstractSubscriber
{
    /**
     * PaymentService
     * @var \Fatchip\CTPayment\CTPaymentService $service
     */
    protected $paymentService;

    protected $plugin;

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
        $params = $request->getParams();
        $session = Shopware()->Session();
        $userData = Shopware()->Modules()->Admin()->sGetUserData();

        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        // ToDo prevent forward to checkout confirm if params are missing

        if ($request->getActionName() === 'saveShippingPayment') {
            $this->updateUserDoB($paymentName, $session->get('sUserId'), $params);
            $this->updateUserPhone($paymentName, $session->get('sUserId'), $params);
            $this->updateUserSSN($paymentName, $session->get('sUserId'), $params);
            $this->updateUserAnnualSalary($paymentName, $session->get('sUserId'), $params);
            $this->updateUserLastschriftBank($paymentName, $session->get('sUserId'), $params);
            $this->updateUserLastschriftIban($paymentName, $session->get('sUserId'), $params);
            $this->updateUserLastschriftKontoinhaber($paymentName, $session->get('sUserId'), $params);
            $this->updateUserAfterpayInstallmentIban($paymentName, $session->get('sUserId'), $params);
            $this->setAfterpayProductNrInSession($params);
            $this->setIssuerInSession($paymentName, $params);
        }
    }


    /**
     * 1. Assigns template variables for the ShippingPayment action
     * 2. Extends templates for AmazonPay and PaypalExpress
     * 3. Extends confirm template for Easycredit to show Easycredit conditions on confirm page
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     * @return void
     */
    public function onPostdispatchFrontendCheckout(\Enlight_Controller_ActionEventArgs $args)
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $subject = $args->getSubject();
        $view = $subject->View();
        $request = $subject->Request();
        $response = $subject->Response();
        $session = Shopware()->Session();
        $params = $request->getParams();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        // TODO: could be removed, when listening to Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout
        if (!$request->isDispatched() || $response->isException()) {
            return;
        }

        if ($request->getActionName() == 'shippingPayment') {

            $birthday = explode('-', $this->utils->getUserDoB($userData));
            $paymentData['birthday'] = $birthday[2];
            $paymentData['birthmonth'] = $birthday[1];
            $paymentData['birthyear'] = $birthday[0];
            $paymentData['phone'] = $this->utils->getUserPhone($userData);
            $paymentData['idealIssuerList'] = Shopware()->Models()->getRepository('Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers')->findAll();
            $paymentData['idealIssuer'] = $session->offsetGet('FatchipComputopIdealIssuer');

            $paymentData['isCompany'] = !empty($userData['billingaddress']['company']);
            $paymentData['lastschriftbank'] = $this->utils->getUserLastschriftBank($userData);
            $paymentData['lastschriftiban'] = $this->utils->getUserLastschriftIban($userData);
            $paymentData['lastschriftkontoinhaber'] = $this->utils->getUserLastschriftKontoinhaber($userData);
            $paymentData['afterpayinstallmentiban'] = $this->utils->getUserAfterpayInstallmentIban($userData);

            $view->assign('FatchipCTPaymentData', $paymentData);

            // assign payment errors and error template to view
            $view->extendsTemplate('frontend/checkout/shipping_payment.tpl');
            // ToDo DO not Display User canceled:22730703 at least for paydirekt
            // logic shouldnt be here or in the template ...

            // use CTError from Session, this was done to prevent csrf Errors from forwarding
            if ($ctError = $session->offsetGet('CTError')) {
                $session->offsetUnset('CTError');
                $params['CTError'] = $ctError;
            }
            $view->assign('CTError', $params['CTError']);
        }

        if ($request->getActionName() == 'confirm' && (strpos($paymentName, fatchip_computop) === 0)) {
            // check for address splitting errors and handle them here
            $util = new Util();
            $ctOrder = new CTOrder();
            try {
                $ctOrder->setBillingAddress($util->getCTAddress($userData['billingaddress']));
                $ctOrder->setShippingAddress($util->getCTAddress($userData['shippingaddress']));
            } catch (\Exception $e) {

                $ctError = [];
                $ctError['CTErrorMessage'] = Shopware()->Snippets()
                    ->getNamespace('frontend/FatchipCTPayment/translations')
                    ->get('errorAddress');
                $ctError['CTErrorCode'] = Shopware()->Snippets()
                    ->getNamespace('frontend/FatchipCTPayment/translations')
                    ->get('errorCode');;
                //$subject->forward('shippingPayment', 'checkout', null, ['CTError' => $ctError]);
                $view->assign('CTError', $ctError);
                return;

            }
        }
    }

    /**
     * Saves date of birth info from template params
     *
     * @param $paymentName
     * @param $userId
     * @param $params
     */
    private function updateUserDoB($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_birthyear'])) {
            $this->utils->updateUserDoB($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_birthyear'] . '-' .
                $params['FatchipComputopPaymentData'][$paymentName . '_birthmonth'] . '-' .
                $params['FatchipComputopPaymentData'][$paymentName . '_birthday']
            );
        }
    }

    /**
     * Saves Phone info from template params in user billing attributes
     *
     * @param $paymentName
     * @param $userId
     * @param $params
     */
    private function updateUserPhone($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_phone'])) {
            $this->utils->updateUserPhone($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_phone']
            );
        }
    }

    /**
     * Saves social security number from template params in user attributes
     * @param $paymentName
     * @param $userId
     * @param $params
     */
    private function updateUserSSN($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_socialsecuritynumber'])) {
            $this->utils->updateUserSSN($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_socialsecuritynumber']
            );
        }
    }

    /**
     * Saves annual salary from template params in user attributes
     * @param $paymentName
     * @param $userId
     * @param $params
     */
    private function updateUserAnnualSalary($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_annualsalary'])) {
            $this->utils->updateUserAnnualSalary($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_annualsalary']
            );
        }
    }

    /**
     * Saves bank info from template params in user attributes
     * @param $paymentName
     * @param $userId
     * @param $params
     */
    private function updateUserLastschriftBank($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_bank'])) {
            $this->utils->updateUserLastschriftBank($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_bank']
            );
        }
    }

    /**
     * Saves iban from template params in user attributes
     * @param $paymentName
     * @param $userId
     * @param $params
     */
    private function updateUserLastschriftIban($paymentName, $userId, $params)
    {
        $pluginConfig = Shopware()->Plugins()->Frontend()->FatchipCTPayment()->Config()->toArray();
        $isIbanAnon = $pluginConfig['lastschriftAnon'] == 'Aus' ? false : true;

        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_iban'])) {
            if (!$isIbanAnon) {
                $this->utils->updateUserLastschriftIban($userId,
                    $params['FatchipComputopPaymentData'][$paymentName . '_iban']
                );
            } elseif (preg_match('#XXXX#', $params['FatchipComputopPaymentData']['fatchip_computop_lastschrift_iban_anon'])) {
                $this->utils->updateUserLastschriftIban($userId,
                    $params['FatchipComputopPaymentData'][$paymentName . '_iban']
                );
            } else {
                $this->utils->updateUserLastschriftIban($userId,
                    $params['FatchipComputopPaymentData'][$paymentName . '_iban_anon']
                );
            }
        }
    }

    /**
     * Saves iban from template params in user attributes
     * @param $paymentName
     * @param $userId
     * @param $params
     */
    private function updateUserAfterpayInstallmentIban($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_iban'])) {
            $this->utils->updateUserAfterpayInstallmentIban($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_iban']
            );
        }
    }

    /**
     * Saves accountholder info from template params in user attributes
     * @param $paymentName
     * @param $userId
     * @param $params
     */
    private function updateUserLastschriftKontoinhaber($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_kontoinhaber'])) {
            $this->utils->updateUserLastschriftKontoinhaber($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_kontoinhaber']
            );
        }
    }

    /**
     * Saves issuer info in session
     *
     * @param $paymentName
     * @param $params
     */
    private function setIssuerInSession($paymentName, $params)
    {
        $session = Shopware()->Session();
        if (!empty($params['FatchipComputopPaymentData']['fatchip_computop_ideal_issuer']) && $paymentName === 'fatchip_computop_ideal') {
            $session->offsetSet('FatchipComputopIdealIssuer',
                $params['FatchipComputopPaymentData']['fatchip_computop_ideal_issuer']
            );
        }
    }

    /**
     * Saves Afterpay ProductNr in Session
     *
     * @param array $params
     */
    private function setAfterpayProductNrInSession($params)
    {
        $session = Shopware()->Session();
        if (!empty($params['FatchipComputopPaymentData']['fatchip_computop_afterpay_installment_productnr'])) {
            $session->offsetSet('FatchipComputopAfterpayProductNr',
                $params['FatchipComputopPaymentData']['fatchip_computop_afterpay_installment_productnr']
            );
        }
    }

    /**
     * gets userData array from OrderVars from Session
     * shoud be overridden in sublcasses if it is needed before an order exists
     * @return mixed
     */
    protected function getUserData()
    {
        return Shopware()->Modules()->Admin()->sGetUserData();
    }

    /**
     * Duplicate methods from payment controller
     * Sets the userData paramater for Computop calls to Shopware Version and Module Version
     * @return string
     */
    public function getUserDataParam()
    {
        return 'Shopware Version: ' . Util::getShopwareVersion() . ', Modul Version: ' . $this->plugin->getVersion();
    }
}
