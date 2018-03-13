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

namespace Shopware\Plugins\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Theme\LessDefinition;
use Shopware\Plugins\FatchipCTPayment\Util;

class Checkout implements SubscriberInterface
{

    /** @var Util $utils * */
    protected $utils;

    /**
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Shopware_Controllers_Frontend_Checkout::saveShippingPaymentAction::after' => 'onAfterPaymentAction',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onPostdispatchFrontendCheckout',
            'Theme_Compiler_Collect_Plugin_Less' => 'onThemeCompilerCollectPluginLess',
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
        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        // Todo check in all sw versions
        // sw 5.0
        // sw 5.1
        // sw 5.2
        // sw 5.3
        // sw 5.4
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

            $this->setIssuerInSession($paymentName, $params);

            if ($paymentName === 'fatchip_computop_easycredit') {
                $subject->redirect(['controller' => 'FatchipCTEasyCredit', 'action' => 'gateway', 'forceSecure' => true]);
            }
        }
    }


    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     * @return void
     */
    public function onPostdispatchFrontendCheckout(\Enlight_Controller_ActionEventArgs $args)
    {
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        $pluginConfig = Shopware()->Plugins()->Frontend()->FatchipCTPayment()->Config()->toArray();
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

            $birthday = explode('-', $this->utils->getUserDoB($userData));
            $paymentData['birthday'] = $birthday[2];
            $paymentData['birthmonth'] = $birthday[1];
            $paymentData['birthyear'] = $birthday[0];
            $paymentData['phone'] = $this->utils->getUserPhone($userData);
            $paymentData['idealIssuerList'] = Shopware()->Models()->getRepository('Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers')->findAll();
            $paymentData['idealIssuer'] = $session->offsetGet('FatchipComputopIdealIssuer');
            //$paymentData['sofortIssuerList'] = Shopware()->Models()->getRepository('Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers')->findAll();
            //$paymentData['sofortIssuer'] = $session->offsetGet('FatchipComputopSofortIssuer');
            $paymentData['isCompany'] = !empty($userData['billingaddress']['company']);
            $paymentData['lastschriftbank'] = $this->utils->getUserLastschriftBank($userData);
            $paymentData['lastschriftiban'] = $this->utils->getUserLastschriftIban($userData);
            $paymentData['lastschriftkontoinhaber'] = $this->utils->getUserLastschriftKontoinhaber($userData);


            if ($this->utils->needSocialSecurityNumberForKlarna()) {
                $paymentData['socialsecuritynumber'] = $this->utils->getuserSSN($userData);
                $paymentData['showsocialsecuritynumber'] = true;
                $paymentData['SSNLabel'] = $this->utils->getSocialSecurityNumberLabelForKlarna($userData);
                $paymentData['SSNMaxLen'] = $this->utils->getSSNLength($userData);
            }

            if ($this->utils->needAnnualSalaryForKlarna($userData)) {
                $paymentData['showannualsalary'] = true;
                $paymentData['annualsalary'] = $this->utils->getUserAnnualSalary($userData);
            }

            // remove AmazonPay and Paypal Express from Payment List
            $payments = $view->getAssign('sPayments');

            foreach ($payments as $index => $payment) {
                if ($payment['name'] === 'fatchip_computop_amazonpay') {
                    $amazonPayIndex = $index;
                }
                if ($payment['name'] === 'fatchip_computop_paypal_express') {
                    $paypalExpressIndex = $index;
                }
                //Klarna is not available for Companies in DE, AT NL
                if ($payment['name'] === 'fatchip_computop_klarna_invoice') {
                    $klarnaInvoiceIndex = $index;
                }
                if ($payment['name'] === 'fatchip_computop_klarna_installment') {
                    $klarnaInsatallmentIndex = $index;
                }

            }

            unset ($payments[$amazonPayIndex]);
            unset ($payments[$paypalExpressIndex]);

            if ($this->utils->isKlarnaBlocked($userData)) {
                unset ($payments[$klarnaInvoiceIndex]);
                unset ($payments[$klarnaInsatallmentIndex]);
            }

            $view->assign('sPayments', $payments);
            $view->assign('FatchipCTPaymentData', $paymentData);

            // assign payment errors and error template to view
            $view->extendsTemplate('frontend/checkout/shipping_payment.tpl');
            // ToDo DO not Display User canceled:22730703 at least for paydirekt
            // logic shouldnt be here or in the template ...
            $view->assign('CTError', $params['CTError']);
        }


        // ToDo find a better way, it would be nice to move this to the Amazon Controller
        if ($this->utils->isAmazonPayActive()) {
            // assign plugin Config to View
            $view->assign('fatchipCTPaymentConfig', $pluginConfig);
            // extend cart and ajax cart with Amazon Button
            $view->extendsTemplate('frontend/checkout/ajax_cart_amazon.tpl');
            $view->extendsTemplate('frontend/checkout/cart_amazon.tpl');
        }

        // ToDo find a better way, it would be nice to move this to the Amazon Controller
        // ToDo refactor both methods to isPaymentactive($paymentName)
        if ($this->utils->isPaypalExpressActive()) {
            // assign plugin Config to View
            $view->assign('fatchipCTPaymentConfig', $pluginConfig);
            // extend cart and ajax cart with Amazon Button
            $view->extendsTemplate('frontend/checkout/ajax_cart_paypal.tpl');
            $view->extendsTemplate('frontend/checkout/cart_paypal.tpl');
        }

        if ($request->getActionName() == 'confirm') {

            $view->extendsTemplate('frontend/checkout/confirm.tpl');

            // add easyCredit Information to view
            if ($session->offsetGet('FatchipComputopEasyCreditInformation')) {
                $view->assign('FatchipComputopEasyCreditInformation', $session->offsetGet('FatchipComputopEasyCreditInformation'));
            }
        }
    }

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

    private function updateUserPhone($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_phone'])) {
            $this->utils->updateUserPhone($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_phone']
            );
        }
    }

    private function updateUserSSN($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_socialsecuritynumber'])) {
            $this->utils->updateUserSSN($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '_socialsecuritynumber']
            );
        }
    }


    private function updateUserAnnualSalary($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_annualsalary'])) {
            $this->utils->updateUserAnnualSalary($userId,
                $params['FatchipComputopPaymentData'][$paymentName . '__annualsalary']
            );
        }
    }

    private function updateUserLastschriftBank($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_bank'])) {
            $this->utils->updateUserLastschriftBank($userId,
              $params['FatchipComputopPaymentData'][ $paymentName . '_bank']
            );
        }
    }

    private function updateUserLastschriftIban($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_iban'])) {
            $this->utils->updateUserLastschriftIban($userId,
              $params['FatchipComputopPaymentData'][ $paymentName . '_iban']
            );
        }
    }

    private function updateUserLastschriftKontoinhaber($paymentName, $userId, $params)
    {
        if (!empty($params['FatchipComputopPaymentData'][$paymentName . '_kontoinhaber'])) {
            $this->utils->updateUserLastschriftKontoinhaber($userId,
              $params['FatchipComputopPaymentData'][ $paymentName . '_kontoinhaber']
            );
        }
    }

    private function setIssuerInSession($paymentName, $params)
    {
        $session = Shopware()->Session();
        if (!empty($params['FatchipComputopPaymentData']['fatchip_computop_ideal_issuer']) && $paymentName === 'fatchip_computop_ideal') {
            $session->offsetSet('FatchipComputopIdealIssuer',
                $params['FatchipComputopPaymentData']['fatchip_computop_ideal_issuer']
            );
        }

        /* if (!empty($params['FatchipComputopPaymentData']['fatchip_computop_sofort_issuer']) && $paymentName === 'fatchip_computop_sofort') {
             $session->offsetSet('FatchipComputopSofortIssuer',
               $params['FatchipComputopPaymentData']['fatchip_computop_sofort_issuer']
             );
         }
        */
    }


    /**
     * @return LessDefinition
     */
    public function onThemeCompilerCollectPluginLess()
    {
        return new LessDefinition(
            [],
            [__DIR__ . '/../Views/frontend/_public/src/less/all.less']
        );
    }

}
