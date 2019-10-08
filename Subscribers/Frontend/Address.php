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
use Enlight_Controller_ActionEventArgs;
use Enlight_Controller_Request_RequestHttp as Request;
use Fatchip\CTPayment\CTPaymentMethods\KlarnaPayments;
use Shopware\Models\Customer\Address as ShopwareAddress;
use Shopware\Plugins\FatchipCTPayment\Util;

class Address implements SubscriberInterface
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
            'Enlight_Controller_Action_PostDispatch_Frontend_Address' => 'onPostDispatchFrontendAddress',
        ];
    }

    /**
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendAddress(Enlight_Controller_ActionEventArgs $args)
    {
        $action = $args->getSubject()->Request()->getActionName();
        switch ($action) {
            case 'ajaxSave':
                $this->onPostDispatchFrontendAddressAjaxSave($args);
                break;
            case 'handleExtra':
                $this->onPostDispatchFrontendAddressHandleExtra($args);
                break;
        }
    }

    /**
     * Save address.
     *
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendAddressAjaxSave(Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Util $utils */
        $utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        $session = Shopware()->Session();
        /** @var Request $request */
        $request = $args->getSubject()->Request();

        $userData = $session->sOrderVariables['sUserData'];
        $usedPaymentID = $userData['additional']['user']['paymentID'];
        $usedPaymentName = $utils->getPaymentNameFromId($usedPaymentID);

        $extraData = $request->getParam('extraData');
        $saveAction = $request->getParam('saveAction');

        if (!(
            stristr($usedPaymentName, 'klarna')            // Klarna payment method
            && $saveAction === 'update'                    // update action
            && is_array($extraData)                        // parameter 'extraData' is array
            && array_key_exists('sessionKey', $extraData)  // 'sessionKey' exists in 'extraData'
        )) {
            return;
        }

        /** @var KlarnaPayments $payment */
        $payment = $utils->createCTKlarnaPayment($userData);

        $sessionKey = $extraData['sessionKey'];
        $address = $request->getParam('address');

        $payId = $session->offsetGet('FatchipCTKlarnaPaymentSessionResponsePayID');
        $eventToken = 'UCA';

        $title = $address['salutation'] === 'mr' ? 'Herr' : 'Frau';
        $company = key_exists('company', $address) ? $address['company'] : '';
        $countryCode = $utils->getCTCountryIso($address['country']);
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

        $billingData = strstr($sessionKey, 'checkoutBillingAddressId') ? $addressData : [];
        $shippingData = strstr($sessionKey, 'checkoutShippingAddressId') ? $addressData : [];

        $payment->storeKlarnaChangeBillingShippingRequestParams($payId, $eventToken, $billingData, $shippingData);

        $utils->requestKlarnaChangeBillingShipping($payment);
    }

    /**
     * Select address.
     *
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendAddressHandleExtra(Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Util $utils */
        $utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        $session = Shopware()->Session();
        /** @var Request $request */
        $request = $args->getSubject()->Request();

        $userData = $session->sOrderVariables['sUserData'];
        $usedPaymentID = $userData['additional']['user']['paymentID'];
        $usedPaymentName = $utils->getPaymentNameFromId($usedPaymentID);

        $extraData = $request->getParam('extraData');

        if (!(
            stristr($usedPaymentName, 'klarna')            // Klarna payment method
            && is_array($extraData)                        // parameter 'extraData' is array
            && array_key_exists('sessionKey', $extraData)  // 'sessionKey' exists in 'extraData'
        )) {
            return;
        }

        /** @var KlarnaPayments $payment */
        $payment = $utils->createCTKlarnaPayment($userData);

        $sessionKey = $extraData['sessionKey'];
        $customerAddressID = $request->getParam('id');
        $addressType = strstr($sessionKey, 'shipping') ? 'shipping' : 'billing';
        /** @var ShopwareAddress $addressObject */
        $addressObject = $utils->getCustomerAddressById($customerAddressID, $addressType);

        $payId = $session->offsetGet('FatchipCTKlarnaPaymentSessionResponsePayID');
        $eventToken = 'UCA';

        $title = $addressObject->getSalutation() === 'mr' ? 'Herr' : 'Frau';
        $countryCode = $utils->getCTCountryIso($addressObject->getCountry()->getIso());
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

        $billingData = strstr($sessionKey, 'checkoutBillingAddressId') ? $addressData : [];
        $shippingData = strstr($sessionKey, 'checkoutShippingAddressId') ? $addressData : [];

        $payment->storeKlarnaChangeBillingShippingRequestParams($payId, $eventToken, $billingData, $shippingData);

        $utils->requestKlarnaChangeBillingShipping($payment);
    }
}
