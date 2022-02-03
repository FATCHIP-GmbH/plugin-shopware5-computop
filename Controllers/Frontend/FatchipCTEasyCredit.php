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
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */

require_once 'FatchipCTPayment.php';

use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Fatchip\CTPayment\CTEnums\CTEnumEasyCredit;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTEasyCredit *
 *
 * @category  Payment_Controller
 * @package   Computop_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 Computop
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.firstcash.com
 */
class Shopware_Controllers_Frontend_FatchipCTEasyCredit extends Shopware_Controllers_Frontend_FatchipFCSPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'EasyCredit';

    /**
     * Shopware basket
     *
     * @var array $basket Basket content
     */
    protected $basket;

    /**
     * Index action method.
     *
     * @return void
     * @throws Exception
     */
    public function indexAction()
    {
        $this->forward('confirm');
    }

    /**
     * Gateway action method.
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        // we have to use this, because there is no order yet
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $this->basket = $this->get('modules')->Basket()->sGetBasket();
        $shippingCosts = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
        $amount = $this->basket['AmountNumeric'] + $shippingCosts['brutto'];

        // TODO refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($amount * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($user['shippingaddress']));
        $ctOrder->setEmail($user['additional']['user']['email']);
        $ctOrder->setCustomerID($user['additional']['user']['id']);

        $payment = $this->paymentService->getIframePaymentClass(
            $this->paymentClass,
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'return', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserDataParam(),
            CTEnumEasyCredit::EVENTTOKEN_INIT
        );

        $payment->setDateOfBirth($this->utils->getUserDoB($user));
        $params = $payment->getRedirectUrlParams();
        $this->session->offsetSet('fatchipCTRedirectParams', $params);
        $this->redirect($payment->getHTTPGetURL($params));
    }

    /**
     * Action is trigerred when the user returns from Easycredit.
     *
     * @return void
     * @throws Exception
     */
    public function returnAction()
    {
        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $requestParams = $this->Request()->getParams();
        $this->basket = $this->get('modules')->Basket()->sGetBasket();
        $shippingCosts = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
        $amount = $this->basket['AmountNumeric'] + $shippingCosts['brutto'];

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($amount * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($userData['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($userData['shippingaddress']));
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);

        $payment = $this->paymentService->getIframePaymentClass(
            $this->paymentClass,
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'confirm', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            'Test',
            $this->getUserDataParam(),
            CTEnumEasyCredit::EVENTTOKEN_GET
        );

        $payment->setDateOfBirth($this->utils->getUserDoB($userData));

        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipCTRedirectParams'), $this->paymentClass, 'REDIRECT', $response);

        switch ($response->getStatus()) {
            case CTEnumStatus::AUTHORIZE_REQUEST:
                // Only save Information to Session if $decision['entscheidung']['entscheidungsergebnis'] is "GRUEN"
                // see https://www.computop.com/fileadmin/user_upload/Downloads_Content/deutsch/Handbuch/Manual_Computop_Paygate_easyCredit.pdf
                // page 11
                $decisionParams = $payment->getDecisionParams($response->getPayID(), $response->getTransID(), $amount * 100, $this->getCurrencyShortName());
                $responseObject = $this->plugin->callComputopService($decisionParams, $payment, 'GET', $payment->getCTCreditCheckURL());
                $decision = json_decode($responseObject->getDecision(), true);

                if (!($decision['entscheidung']['entscheidungsergebnis'] === 'GRUEN')) {
                    $this->forward('failure');
                } else {

                    $this->session->offsetSet('FatchipComputopEasyCreditInformation', $this->getConfirmPageInformation($responseObject));
                    $this->session->offsetSet('fatchipComputopEasyCreditPayId', $response->getPayID());
                    $this->redirect(['controller' => 'checkout', 'action' => 'confirm']);
                }
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Success action method.
     *
     * This method is triggered after the customer successfully
     * finishes the order on the confirm page
     *
     * @return void
     * @throws Exception
     */
    public function confirmAction()
    {
        $orderVars = $this->session->sOrderVariables;
        $userData = $orderVars['sUserData'];
        $this->basket = $this->get('modules')->Basket()->sGetBasket();
        $shippingCosts = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();
        $amount = $this->basket['AmountNumeric'] + $shippingCosts['brutto'];

        // TODO refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($amount * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($userData['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($userData['shippingaddress']));
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);

        $payment = $this->paymentService->getIframePaymentClass(
            $this->paymentClass,
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'success', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserDataParam(),
            CTEnumEasyCredit::EVENTTOKEN_CON
        );

        $payment->setDateOfBirth($this->utils->getUserDoB($userData));
        $params = $payment->getConfirmParams($this->session->offsetGet('fatchipComputopEasyCreditPayId'));
        $response = $this->plugin->callComputopService($params, $payment, 'CON', $payment->getCTCreditCheckURL());

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResult($response);
                $this->session->offsetUnSet('FatchipComputopEasyCreditInformation');

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);
                $this->forward('finish', 'checkout', null, ['sAGB' => 1, 'sUniqueID' => $response->getPayID()]);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Gets inforation from response to be displayed on the order confirmation page
     *
     * @param \Fatchip\CTPayment\CTResponse $responseObject Easycredit financing information
     *
     * @return array
     */
    private function getConfirmPageInformation($responseObject)
    {
        $easyCreditInformation = [];
        $process = json_decode($responseObject->getProcess(), true);
        $financing = json_decode($responseObject->getFinancing(), true);
        $easyCreditInformation['anzahlRaten'] = $financing['ratenplan']['zahlungsplan']['anzahlRaten'];
        $easyCreditInformation['tilgungsplanText'] = $financing['tilgungsplanText'];
        $easyCreditInformation['urlVorvertraglicheInformationen'] = $process['allgemeineVorgangsdaten']['urlVorvertraglicheInformationen'];
        $easyCreditInformation['bestellwert'] = $financing['finanzierung']['bestellwert'];
        $easyCreditInformation['anfallendeZinsen'] = $financing['ratenplan']['zinsen']['anfallendeZinsen'];
        $easyCreditInformation['gesamtsumme'] = $financing['ratenplan']['gesamtsumme'];
        $easyCreditInformation['effektivzins'] = $financing['ratenplan']['zinsen']['effektivzins'];
        $easyCreditInformation['nominalzins'] = $financing['ratenplan']['zinsen']['nominalzins'];
        $easyCreditInformation['betragRate'] = $financing['ratenplan']['zahlungsplan']['betragRate'];
        $easyCreditInformation['betragLetzteRate'] = $financing['ratenplan']['zahlungsplan']['betragLetzteRate'];
        $easyCreditInformation['urlVorvertraglicheInformationen'] = $process['allgemeineVorgangsdaten']['urlVorvertraglicheInformationen'];
        $easyCreditInformation['urlVorvertraglicheInformationen'] = $process['allgemeineVorgangsdaten']['urlVorvertraglicheInformationen'];
        return $easyCreditInformation;
    }
}
