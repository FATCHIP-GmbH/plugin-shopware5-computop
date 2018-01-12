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

use Fatchip\CTPayment\CTPaymentMethodsIframe\CreditCard;
use Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseCreditCard;
use Fatchip\CTPayment\CTPaymentMethodsIframe\EasyCredit;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTAddress\CTAddress;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Fatchip\CTPayment\CTEnums\CTEnumEasyCredit;
use Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseEasyCredit;
use VIISON\AddressSplitter\AddressSplitter;

class Shopware_Controllers_Frontend_FatchipCTPayment extends Shopware_Controllers_Frontend_Payment
{

    const PAYMENTSTATUSPAID = 12;

    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['notify'];
    }

    public function preDispatch()
    {
    }

    public function indexAction()
    {
        /**
         * Check if one of the payment methods is selected. Else return to default controller.
         */
        switch ($this->getPaymentShortName()) {
            case 'fatchip_computop_creditcard':
                return $this->redirect(['action' => 'gateway', 'forceSecure' => true]);
            case 'fatchip_computop_easycredit':
                return $this->redirect(['action' => 'accepted_conditions', 'forceSecure' => true]);
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }

    public function gatewayAction()
    {
        $router = $this->Front()->Router();
        $user = $this->getUser();

        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $config = $plugin->Config()->toArray();
        // ToDo: handle possible exception here
        $service = $this->container->get('FatchipCTPaymentApiClient');

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($this->getAmount());
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->getCTAddress($user['shippingaddress']));

        // ToDo should this be done in the CTPaymentService?
        $myCC = new CreditCard(
            $config,
            $ctOrder,
            $router->assemble(['action' => 'index', 'forceSecure' => true]),
            $router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $router->assemble(['action' => 'notify', 'forceSecure' => true])
        );
        $myCC->setUserData($service->createPaymentToken($this->getAmount(), $user['billing']['customernumber']));
        $this->redirect($myCC->getHTTPGetURL());

    }

    public function gatewayEasycreditAction()
    {
        $router = $this->Front()->Router();
        $user = $this->getUser();

        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $config = $plugin->Config()->toArray();
        // ToDo: handle possible exception here
        $service = $this->container->get('FatchipCTPaymentApiClient');

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($this->getAmount() * 10000);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->getCTAddress($user['shippingaddress']));

        $myEC = new EasyCredit($config, $ctOrder,
            $router->assemble(['action' => 'auth_success', 'forceSecure' => true]),
            $router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $router->assemble(['action' => 'notify', 'forceSecure' => true]),
            CTEnumEasyCredit::EVENTTOKEN_INIT
        );
        $myEC->setUserData($service->createPaymentToken($this->getAmount(), $user['billingaddress']['customernumber']));
        // ToDo this works only in >SW 5.2 -> refactor
        $myEC->setDateOfBirth($user['additional']['user']['birthday']);

        $this->redirect($myEC->getHTTPGetURL());

    }

    public function acceptedConditionsAction()
    {
        $requestParams = $this->Request()->getParams();
        $router = $this->Front()->Router();
        $user = $this->getUser();
        $session = Shopware()->Session();

        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $config = $plugin->Config()->toArray();
        // ToDo: handle possible exception here
        $service = $this->container->get('FatchipCTPaymentApiClient');

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($this->getAmount() * 10000);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->getCTAddress($user['shippingaddress']));

        $myEC = new EasyCredit($config, $ctOrder,
            $router->assemble(['action' => 'confirm_conditions', 'forceSecure' => true]),
            $router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $router->assemble(['action' => 'notify', 'forceSecure' => true]),
            CTEnumEasyCredit::EVENTTOKEN_CON
        );
        $myEC->confirm($session->offsetGet('fatchipComputopEasyCreditPayId'));
        $myEC->setUserData($service->createPaymentToken($this->getAmount(), $user['billingaddress']['customernumber']));
        // ToDo this works only in >SW 5.2 -> refactor
        $myEC->setDateOfBirth($user['additional']['user']['birthday']);
        $this->saveOrder(
            $session->offsetGet('fatchipComputopEasyCreditPayId'),
            'Test',
            self::PAYMENTSTATUSPAID
        );

        $this->redirect(['controller' => 'checkout', 'action' => 'finish']);

    }

    /**
     * Cancel action method
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();

        /** @var \Fatchip\CTPayment\CTPaymentService $service */
        $service = $this->container->get('FatchipCTPaymentApiClient');

        $response = $service->createPaymentResponse($requestParams);
        // ToDo extend shippingPayment template to show errors instead of dying ;)
        die($response->getDescription());
        return $this->redirect(['controller' => 'checkout', 'action' => 'shippingPayment', 'sTarget' => 'checkout']);
    }

    /**
     * Cancel action method
     */
    public function successAction()
    {
        $requestParams = $this->Request()->getParams();
        $user = $this->getUser();

        /** @var \Fatchip\CTPayment\CTPaymentService $service */
        $service = $this->container->get('FatchipCTPaymentApiClient');

        /** @var CTResponseCreditCard $response */
        $response = $service->createPaymentResponse($requestParams);
        $token = $service->createPaymentToken($this->getAmount(), $user['billingaddress']['customernumber']);

        if (!$service->isValidToken($response, $token)) {
            $this->forward('failure');
            return;
        }
        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $this->saveOrder(
                    $response->getTransID(),
                    $response->getUserData(),
                    self::PAYMENTSTATUSPAID
                );
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Cancel action method
     */
    public function authSuccessAction()
    {
        $router = $this->Front()->Router();
        $requestParams = $this->Request()->getParams();
        $user = $this->getUser();
        $session = Shopware()->Session();

        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $config = $plugin->Config()->toArray();
        // ToDo: handle possible exception here
        $service = $this->container->get('FatchipCTPaymentApiClient');

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($this->getAmount() * 10000);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($this->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($this->getCTAddress($user['shippingaddress']));

        // set CC Params and request iFrame Url
        // should this be done in the CTPaymentService?
        $myEC = new EasyCredit($config, $ctOrder,
            $router->assemble(['action' => 'auth_success', 'forceSecure' => true]),
            $router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $router->assemble(['action' => 'notify', 'forceSecure' => true]),
            CTEnumEasyCredit::EVENTTOKEN_GET
        );
        $myEC->setUserData($service->createPaymentToken($this->getAmount(), $user['billingaddress']['customernumber']));
        $myEC->setDateOfBirth($user['additional']['user']['birthday']);

        /** @var CTResponseEasyCredit $response */
        $response = $service->createECPaymentResponse($requestParams);
        switch ($response->getStatus()) {
            case CTEnumStatus::AUTHORIZE_REQUEST:
                $responseObject = $myEC->getDecision($response->getPayID());
                $decision = json_decode($responseObject->getDesicion());
                $process = json_decode($responseObject->getProcess());
                $financing = json_decode($responseObject->getFinancing());

                $session->offsetSet('fatchipComputopEasyCreditDecision', $decision);
                $session->offsetSet('fatchipComputopEasyCreditProcess', $process);
                $session->offsetSet('fatchipComputopEasyCreditFinancing', $financing);
                $session->offsetSet('fatchipComputopEasyCreditPayId', $responseObject->getPayID());
                $this->redirect(['controller' => 'checkout', 'action' => 'confirm']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * @param array $swAddress
     * @return CTAddress
     */
    private function getCTAddress(array $swAddress)
    {
        $splitAddress = AddressSplitter::splitAddress($swAddress['street']);

        return new CTAddress(
            ($swAddress['salutation'] == 'mr') ? 'Herr' : 'Frau',
            $swAddress['firstname'],
            $swAddress['lastname'],
            $splitAddress['streetName'],
            $splitAddress['houseNumber'],
            $swAddress['zipcode'],
            $swAddress['city'],
            $this->getCTCountry($swAddress['countryId']),
            // ToDo does this correspond to additional_address_lines?
            $swAddress['additional_address_line1']
        );
    }

    /**
     * ToDo check if we can reliably get country from user object in all SW versions
     * (SW5.2: user['additional']['country']['countryiso'])
     * (SW5.0-5.1: )
     * @param $countryId
     * @return string
     */
    private function getCTCountry($countryId)
    {
        $countrySql = 'SELECT countryiso FROM s_core_countries WHERE id=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryId]);
    }
}

/*      $url = $myCC->getForm();

        $query = $myCC->getTransactionQuery();
        $Len = mb_strlen($query);

        $postdata['MerchantID'] = $merchantID;
        $postdata['Len'] = $Len;
        $postdata['Data'] = $myCC->getEncryptedData();
        $postdata['Background'] = 'https://www.google.de/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png';
        foreach ( $postdata as $key => $value) {
            $post_items[] = $key . '=' . $value;
        }
        $post_string = implode ('&', $post_items);
        $curl_connection =
            curl_init($myCC->getCTPaymentURL());
        //set options
        curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl_connection, CURLOPT_USERAGENT,
            "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);

        //set data to be posted
        curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);

        //perform our request

        $url = curl_exec($curl_connection);
*/