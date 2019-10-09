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
use Enlight_Components_Session_Namespace;
use Enlight_Controller_ActionEventArgs;
use Enlight_Controller_Request_RequestHttp as Request;
use Fatchip\CTPayment\CTPaymentMethods\KlarnaPayments;
use Shopware\Models\Customer\Address as ShopwareAddress;
use Shopware\Plugins\FatchipCTPayment\Util;

class Address implements SubscriberInterface
{
    /** @var Util $utils */
    protected $utils;
    /** @var Enlight_Components_Session_Namespace $session */
    protected $session;
    /** @var Request $request */
    protected $request;

    public function __construct()
    {
    }

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
            'Enlight_Controller_Action_PostDispatch_Frontend_Address' => 'onPostDispatchFrontendAddress',

        ];
    }

    /**
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendAddress(Enlight_Controller_ActionEventArgs $args)
    {
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        $this->session = Shopware()->Session();
        $this->request = $args->getSubject()->Request();

        $action = $this->request->getActionName();

        $userData = $this->session->sOrderVariables['sUserData'];
        $usedPaymentID = $userData['additional']['user']['paymentID'];
        $usedPaymentName = $this->utils->getPaymentNameFromId($usedPaymentID);

        $extraData = $this->request->getParam('extraData');

        $actions = [
            'ajaxSave',
            'handleExtra',
        ];

        if (!(
            stristr($usedPaymentName, 'klarna')            // Klarna payment method
            && in_array($action, $actions)                 // one of the relevant actions
            && is_array($extraData)                        // parameter 'extraData' is array
            && array_key_exists('sessionKey', $extraData)  // 'sessionKey' exists in 'extraData'
        )) {
            return;
        }

        /** @var KlarnaPayments $payment */
        $payment = $this->utils->createCTKlarnaPayment();

        $sessionKey = $extraData['sessionKey'];

        $payId = $this->session->offsetGet('FatchipCTKlarnaPaymentSessionResponsePayID');

        $eventToken = 'UCA';

        $addressData = $this->addressData($sessionKey);

        $billingData = strstr($sessionKey, 'checkoutBillingAddressId') ? $addressData : [];
        $shippingData = strstr($sessionKey, 'checkoutShippingAddressId') ? $addressData : [];

        $payment->storeKlarnaChangeBillingShippingRequestParams($payId, $eventToken, $billingData, $shippingData);

        $this->utils->requestKlarnaChangeBillingShipping($payment);
    }

    /**
     * TODO: maybe this can be refactored to utils, so that other payment methods can use it?
     *
     * @param $sessionKey
     *
     * @return array
     */
    private function addressData($sessionKey)
    {
        $action = $this->request->getActionName();
        $addressData = [];

        switch ($action) {
            case 'ajaxSave':
                $address = $this->request->getParam('address');

                $title = $address['salutation'] === 'mr' ? 'Herr' : 'Frau';
                $company = key_exists('company', $address) ? $address['company'] : '';
                $countryCode = $this->utils->getCTCountryIso($address['country']);
                $addressData = [
                    'Title' => $title,
                    'FirstName' => $address['firstname'],
                    'LastName' => $address['lastname'],
                    'Company' => $company,
                    'Street' => $address['street'],
                    'AddrAddition' => '',
                    'Zip' => $address['zipcode'],
                    'City' => $address['city'],
                    'Region' => '',
                    'CountryCode' => $countryCode,
                    'Email' => '',
                    'Phone' => '',
                ];
                break;
            case 'handleExtra':
                $customerAddressID = $this->request->getParam('id');
                /** @var ShopwareAddress $addressObject */
                $addressType = strstr($sessionKey, 'shipping') ? 'shipping' : 'billing';
                $addressObject = $this->utils->getCustomerAddressById($customerAddressID, $addressType);

                $title = $addressObject->getSalutation() === 'mr' ? 'Herr' : 'Frau';
                $countryCode = $this->utils->getCTCountryIso($addressObject->getCountry()->getIso());
                $addressData = [
                    'Title' => $title,
                    'FirstName' => $addressObject->getFirstname(),
                    'LastName' => $addressObject->getLastname(),
                    'Company' => $addressObject->getCompany(),
                    'Street' => $addressObject->getStreet(),
                    'AddrAddition' => $addressObject->getAdditionalAddressLine1(),
                    'Zip' => $addressObject->getZipcode(),
                    'City' => $addressObject->getCity(),
                    'Region' => '',
                    'CountryCode' => $countryCode,
                    'Email' => '',
                    'Phone' => $addressObject->getPhone(),
                ];
                break;
        }

        return $addressData;
    }
}
