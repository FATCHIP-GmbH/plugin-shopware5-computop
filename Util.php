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

namespace Shopware\Plugins\FatchipCTPayment;

use Fatchip\CTPayment\CTAddress\CTAddress;
use VIISON\AddressSplitter\AddressSplitter;
use Shopware;

require_once 'Components/Api/vendor/autoload.php';

/**
 * Class Util
 * @package Shopware\Plugins\FatchipCTPayment
 */
class Util
{
    /**
     * creates a CTAddress object from a Shopware address array
     * @param array $swAddress
     * @return CTAddress
     * @throws \Exception
     */
    public function getCTAddress(array $swAddress)
    {
        $splitAddress = AddressSplitter::splitAddress($swAddress['street']);

        return new CTAddress(
            ($swAddress['salutation'] == 'mr') ? 'Herr' : 'Frau',
            $swAddress['company'],
            ($swAddress['firstname']) ? $swAddress['firstname'] : $swAddress['firstName'],
            ($swAddress['lastname']) ? $swAddress['lastname'] : $swAddress['lastName'],
            $splitAddress['streetName'],
            $splitAddress['houseNumber'],
            ($swAddress['zipcode']) ? $swAddress['zipcode'] : $swAddress['zipCode'],
            $swAddress['city'],
            $this->getCTCountryIso($this->getCountryIdFromAddress($swAddress)),
            $this->getCTCountryIso3($this->getCountryIdFromAddress($swAddress)),
            // ToDo does this correspond to additional_address_lines?
            $swAddress['additional_address_line1']
        );
    }

    /**
     * Use getCountryIdFromAddress() to get the countryId
     * for all Shopware Versions
     * @param $countryId
     * @return string
     */
    public function getCTCountryIso($countryId)
    {
        $countrySql = 'SELECT countryiso FROM s_core_countries WHERE id=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryId]);
    }

    /**
     * @ignore <description>
     * @param $countryId
     * @return string
     */
    public function getCTCountryIso3($countryId)
    {
        $countrySql = 'SELECT iso3 FROM s_core_countries WHERE id=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryId]);
    }

    /**
     * @ignore <description>
     * @param $countryIso
     * @return string
     */
    public function getCountryIdFromIso($countryIso)
    {
        $countrySql = 'SELECT id FROM s_core_countries WHERE countryiso=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryIso]);
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * gets the user customer number from user array, depending on SW version
     * @param $user
     * @return null
     */
    public function getUserCustomerNumber($user)
    {
        $customerNumber = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $customerNumber = $user['billing']['customernumber'];
        } else {
            $customerNumber = $user['billingaddress']['customernumber'];
        }
        return $customerNumber;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * gets the users Date of Birth from user array, depending on SW version
     * @param $user
     * @return null
     */
    public function getUserDoB($user)
    {
        $birthdate = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $birthdate = isset($user['billing']['birthday']) ? $user['billing']['birthday'] : $user['additional']['user']['birthday'];
        } else {
            $birthdate = $user['billingaddress']['birthday'];
        }
        return $birthdate;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * gets user phone number from database
     * @param $user
     * @return mixed
     */
    public function getUserPhone($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $billing = $user->getDefaultBillingAddress();
        } else {
            $billing = $user->getBilling();
        }

        return $billing->getPhone();
    }

    /**
     * gets Users Socical Security Number from Customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserSSN($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        return $attribute->getFatchipctSocialsecuritynumber();
    }

    /**
     * gets users Annual Salary from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserAnnualSalary($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        return $attribute->getFatchipctAnnualSalary();
    }

    /**
     * gets Bank for Lastschrift from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserLastschriftBank($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        return $attribute->getFatchipctLastschriftbank();
    }

    /**
     * gets Iban for Lastschrift from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserLastschriftIban($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        return $attribute->getFatchipctLastschriftiban();
    }

    /**
     * gets Iban for Afterpay from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserAfterpayInstallmentIban($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        return $attribute->getFatchipctAfterpayinstallmentiban();
    }

    /**
     * gets Accountowner for Lastschrift from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserLastschriftKontoinhaber($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        return $attribute->getFatchipctLastschriftaccowner();
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * gets CountryID from shopware address
     * @param $swAddress
     * @return null
     */
    public function getCountryIdFromAddress($swAddress)
    {
        $countryId = null;
        /*
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $countryId = $swAddress['countryId'];
        } else {
            $countryId = $swAddress['countryID'];
        }
        */
        $countryId = ($swAddress['countryId']) ? $swAddress['countryId'] : $swAddress['countryID'];
        return $countryId;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * updates users Date of Birth in Customer atrributes
     * @param $userId
     * @param $birthday
     */
    public function updateUserDoB($userId, $birthday)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $user->setBirthday($birthday);
            Shopware()->Models()->persist($user);
            Shopware()->Models()->flush($user);

        } else {
            $billing = $user->getBilling();
            $billing->setBirthday($birthday);
            Shopware()->Models()->persist($billing);
            Shopware()->Models()->flush($billing);
        }
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 -
    // 5.3 -
    /**
     * updates users phone number in customer attributes
     * @param $userId
     * @param $phone
     */
    public function updateUserPhone($userId, $phone)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $billing = $user->getDefaultBillingAddress();
        } else {
            $billing = $user->getBilling();
        }
        $billing->setPhone($phone);
        Shopware()->Models()->persist($billing);
        Shopware()->Models()->flush($billing);
    }


    /**
     * updates users Social Security Number in customer attributes
     * @param $userId
     * @param $ssn
     */
    public function updateUserSSN($userId, $ssn)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctSocialsecuritynumber($ssn);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates users Annual Salary in customer attributes
     * @param $userId
     * @param $ssn
     */
    public function updateUserAnnualSalary($userId, $ssn)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctAnnualSalary($ssn);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates users Bank for Lastschrift in customer attributes
     * @param $userId
     * @param $bank
     */
    public function updateUserLastschriftBank($userId, $bank)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctLastschriftbank($bank);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates users Iban for lastschrift in customer attributes
     * @param $userId
     * @param $iban
     */
    public function updateUserLastschriftIban($userId, $iban)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctLastschriftiban($iban);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates users Iban for Afterpay in customer attributes
     * @param $userId
     * @param $iban
     */
    public function updateUserAfterpayInstallmentIban($userId, $iban)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctAfterpayinstallmentiban($iban);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates user account owner in customer attributes
     * @param $userId
     * @param $kontoinhaber
     */
    public function updateUserLastschriftKontoinhaber($userId, $kontoinhaber)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctLastschriftaccowner($kontoinhaber);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * Loads customer adress by ID
     * @param $id
     * @param $type
     * @return null|object
     */
    public function getCustomerAddressById($id, $type)
    {
        if (version_compare(\Shopware::VERSION, '5.2.0', '<')) {
            $address = $type == 'shipping' ? $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Shipping')->find($id) :
                $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Billing')->find($id);
        } else {
            $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address')->find($id);
        }
        return $address;
    }

    /**
     * returns payment name
     *
     * @param string $paymentID
     * @return string
     */
    public function getPaymentNameFromId($paymentID)
    {
        $sql = 'SELECT `name` FROM `s_core_paymentmeans` WHERE id = ?';
        return Shopware()->Db()->fetchOne($sql, $paymentID);
    }

    /**
     * returns payment name
     *
     * @param string $paymentName
     * @return string
     */
    public function getPaymentIdFromName($paymentName)
    {
        $sql = 'SELECT `id` FROM `s_core_paymentmeans` WHERE name = ?';
        return Shopware()->Db()->fetchOne($sql, $paymentName);
    }

    /**
     * Saves CRIF result in Address attributes
     * @param $addressID
     * @param $type - billing or shipping
     * @param $response
     */
    public function saveCRIFResultInAddress($addressID, $type, $response)
    {
        if (!$addressID) {
            return;
        }

        $address = $this->getCustomerAddressById($addressID, $type);
        if ($attribute = $address->getAttribute()) {
            $attribute->setFatchipctCrifdate(date('Y-m-d H:i:s'));
            $attribute->setFatchipctCrifdescription($response->getDescription());
            $attribute->setFatchipctCrifresult($response->getResult());
            $attribute->setFatchipctCrifstatus($response->getStatus());
            Shopware()->Models()->persist($attribute);
            Shopware()->Models()->flush();
        }
    }

    /**
     * checks if AmazonPay is enabled
     *
     * @return bool
     */
    public function isAmazonPayActive()
    {
        $payment = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->findOneBy(
            ['name' => 'fatchip_computop_amazonpay']
        );
        return $payment->getActive();
    }

    /**
     * checks if Papyal is enabled
     * ToDO refactor to generic method
     *
     * @return bool
     */
    public function isPaypalExpressActive()
    {
        $payment = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->findOneBy(
            ['name' => 'fatchip_computop_paypal_express']
        );
        return $payment->getActive();
    }

    /**
     * checks social security number should be collected for this order.
     * SSN is needed in DK, FI, SE and NO and not in other countries
     * @return bool
     */
    public function needSocialSecurityNumberForKlarna()
    {
        if ($countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'DK' || $countryIso == 'FI' || $countryIso == 'SE' || $countryIso == 'NO') {
                return true;
            }
        }

        return false;
    }

    /**
     * * Klarna is only available for Private persons in DE, AT and NL. So for companies we block Klarna in these countries
     * @param $userData
     * @return bool
     */
    public function isKlarnaBlocked($userData)
    {

        $countryIso = $this->getBillingIsoForCurrentOrder();

        if ($countryIso == 'DE' || $countryIso == 'AT' || $countryIso == 'NL') {
            if (!empty($userData['billingaddress']['company'])) {
                return true;
            }
        }

        return false;
    }


    /**
     * returns the label for the SSN field for klarna
     * For comapanies the label is Handelsregisternummer, for NO we add last 5 digits
     * @param $userData
     * @return string
     */
    public function getSocialSecurityNumberLabelForKlarna($userData)
    {
        $label = 'Sozialversicherungsnummer (letzte 4 Ziffern)';
        //For comapnies, the field is called Handelsregisternummer
        if (isset($userData['billingaddress']['company'])) {
            $label = '
            Handelsregisternummer';
        } else if ($countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'NO') {
                $label = 'Sozialversicherungsnummer (letzte 5 Ziffern)';
            }
        }

        return $label;
    }

    /**
     * Annual salary is mandatory for Private Customers in Denmark
     * @param $userData
     * @return bool
     */
    public function needAnnualSalaryForKlarna($userData)
    {
        if (!isset($userData['billingaddress']['company']) && $countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'DK') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the length for the SSN field - for NO its 5, for other countries 4. For companies there is no max length
     * @param $userData
     * @return int|null
     */
    public function getSSNLength($userData)
    {
        //for companies, we do not need a max length
        if (!isset($userData['billingaddress']['company']) && $countryIso = $this->getBillingIsoForCurrentOrder()) {
            if ($countryIso == 'NO') {
                return 5;
            }
            return 4;
        }
        return null;
    }

    /**
     * returns the ISO country code for the billing address of the current order
     * @return null|string
     */
    private function getBillingIsoForCurrentOrder()
    {
        if ($orderVars = Shopware()->Session()->sOrderVariables) {
            $userData = $orderVars['sUserData'];
            $countryID = $this->getCountryIdFromAddress($userData['billingaddress']);
        } else if ($user = Shopware()->Modules()->Admin()->sGetUserData()) {
            $countryID = $this->getCountryIdFromAddress($user['billingaddress']);
        }

        if ($countryID) {
            return $this->getCTCountryIso($countryID);
        }

        return null;

    }

    /**
     * Return whether or not a abo-article is in the basket
     *
     * @return bool
     * @throws \Exception
     */
    public function isAboCommerceArticleInBasket()
    {
        if (!$this->isAboCommerceActive()) {
            return false;
        }

        $entityManager = Shopware()->Models();
        $builder = $entityManager->createQueryBuilder();
        $builder->select($entityManager->getExpressionBuilder()->count('basket.id'))
            ->from('Shopware\Models\Order\Basket', 'basket')
            ->innerJoin('basket.attribute', 'attribute')
            ->where('basket.sessionId = :sessionId')
            ->andWhere('attribute.swagAboCommerceDeliveryInterval IS NOT NULL')
            ->setParameters(array('sessionId' => Shopware()->SessionID()));

        $count = $builder->getQuery()->getSingleScalarResult();

        return (bool)$count;
    }

    /**
     * check if abo commerce plugin is activated
     *
     * @return bool
     */
    protected function isAboCommerceActive()
    {
        $sql = "SELECT 1 FROM s_core_plugins WHERE name='SwagAboCommerce' AND active=1";

        $result = Shopware()->Db()->fetchOne($sql);
        if ($result != 1) {
            return false; //not installed
        }
        return true;
    }

    /**
     * Remove whitespaces from input string
     * @param $input String
     * @return string without whitespaces
     */
    public function removeWhitespaces($input)
    {
        return preg_replace('/\s+/', '', $input);
    }

    /** retrieve json config file from afterbuy
     * @param $merchantId String
     * @param $userData array
     * @return bool
     */
    public function afterpayProductExistsforBasketValue($merchantId, $userData, $fallback = true)
    {
        $countryCode = strtolower($userData['additional']['country']['countryiso']);
        $afterpayMerchantId = 'CP_'.$merchantId;
        $orderVars = Shopware()->Session()->sOrderVariables->getArrayCopy();
        $basket = $orderVars['sBasket'];

        $handle = curl_init('https://cdn.myafterpay.com/config/'.$countryCode.'/'.$afterpayMerchantId.'json');
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if($httpCode == 404) {
            if (!$fallback) {
                return false;
            } else {
                $handle = curl_init('https://cdn.myafterpay.com/config/de/4564.json');
                curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
                /* Get the HTML or whatever is linked in $url. */
                $response = curl_exec($handle);
                curl_close($handle);
            }
        }
        $availableProducts = json_decode($response);
        $minBasketValues = array_column($availableProducts->availablePaymentMethods->installments, 'minAmount');
        $maxBasketValues = array_column($availableProducts->availablePaymentMethods->installments, 'maxAmount');

        $min = min($minBasketValues);
        $max = max($maxBasketValues);

        return ($basket['AmountNumeric'] >= $min && $basket['AmountNumeric'] <= $max);
    }
}
