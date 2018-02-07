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

use Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseCreditCard;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Fatchip\CTPayment\CTEnums\CTEnumEasyCredit;
use Fatchip\CTPayment\CTPaymentMethodsIframe\EasyCredit;

// add baseclass via require_once so we can extend
// ToDo find a better solution for this
require_once 'FatchipCTPayment.php';

/**
 * Class Shopware_Controllers_Frontend_FatchipCTEasyCredit
 */
class Shopware_Controllers_Frontend_FatchipCTEasyCredit extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    public function indexAction()
    {
        $this->forward('confirm');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $user = Shopware()->Modules()->Admin()->sGetUserData();

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($this->getAmount() * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($user['shippingaddress']));
        $ctOrder->setEmail($user['additional']['user']['email']);
        $ctOrder->setCustomerID($user['additional']['user']['id']);

        $payment = $this->getPaymentClass($ctOrder, 'return');

        $payment->setDateOfBirth($this->utils->getUserDoB($user));

        $this->redirect($payment->getHTTPGetURL());
    }

    /**
     *
     * Action is trigerred when the user returns from Easycredit
     * @return void
     */
    public function returnAction()
    {
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $session = Shopware()->Session();
        $requestParams = $this->Request()->getParams();

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($this->getAmount() * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($user['shippingaddress']));
        $ctOrder->setEmail($user['additional']['user']['email']);
        $ctOrder->setCustomerID($user['additional']['user']['id']);

        $payment = $this->getPaymentClass($ctOrder, 'confirm', CTEnumEasyCredit::EVENTTOKEN_GET);

        $payment->setDateOfBirth($this->utils->getUserDoB($user));

        /** @var CTResponseEasyCredit $response */
        $response = $this->paymentService->createECPaymentResponse($requestParams);
        switch ($response->getStatus()) {
            case CTEnumStatus::AUTHORIZE_REQUEST:

                // Only save Information to Session if $decision['entscheidung']['entscheidungsergebnis'] is "GRUEN"
                // see https://www.computop.com/fileadmin/user_upload/Downloads_Content/deutsch/Handbuch/Manual_Computop_Paygate_easyCredit.pdf
                // page 11

                $responseObject = $payment ->getDecision($response->getPayID());
                $decision = json_decode($responseObject->getDesicion(), true);

                if (!($decision['entscheidung']['entscheidungsergebnis'] === 'GRUEN')){
                    $this->forward('failure');
                    break;
                }

                $session->offsetSet('FatchipComputopEasyCreditInformation', $this->getConfirmPageInformation($responseObject));
                $session->offsetSet('fatchipComputopEasyCreditPayId', $response->getPayID());

                $this->redirect(['controller' => 'checkout', 'action' => 'confirm']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * success action method
     * @return void
     * @throws Exception
     */
    public function confirmAction()
    {
        $user = Shopware()->Modules()->Admin()->sGetUserData();
        $session = Shopware()->Session();

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        //important: multiply amount by 100
        $ctOrder->setAmount($this->getAmount() * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->utils->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->utils->getCTAddress($user['shippingaddress']));
        $ctOrder->setEmail($user['additional']['user']['email']);
        $ctOrder->setCustomerID($user['additional']['user']['id']);

        $payment = $this->getPaymentClass($ctOrder, 'success', CTEnumEasyCredit::EVENTTOKEN_CON);

        $payment->setDateOfBirth($this->utils->getUserDoB($user));

        $response = $payment->confirm($session->offsetGet('fatchipComputopEasyCreditPayId'));

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $this->saveOrder(
                    $response->getTransID(),
                    $response->getUserData(),
                    self::PAYMENTSTATUSPAID
                );

                $session->offsetSet('FatchipComputopEasyCreditInformation', null);

                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * notify action method
     * @return void
     * @throws Exception
     */
    public function notifyAction()
    {
    }

    private function getConfirmPageInformation ($responseObject){
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

        return $easyCreditInformation;
    }

    public function getPaymentClass($order, $successAction = '', $eventToken = CTEnumEasyCredit::EVENTTOKEN_INIT ) {
        $router = $this->Front()->Router();

        return new EasyCredit(
          $this->config,
          $order,
          $router->assemble(['action' => $successAction, 'forceSecure' => true]),
          $router->assemble(['action' => 'failure', 'forceSecure' => true]),
          $router->assemble(['action' => 'notify', 'forceSecure' => true]),
          $this->getUserData(),
          $this->getOrderDesc(),
          $eventToken
           );
    }
}
