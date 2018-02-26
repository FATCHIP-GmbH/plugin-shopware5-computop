<?php
/**
 * Created by PhpStorm.
 * User: stefstar
 * Date: 1/12/18
 * Time: 7:28 PM
 */

namespace Shopware\Plugins\FatchipCTPayment;

use Fatchip\CTPayment\CTAddress\CTAddress;
use VIISON\AddressSplitter\AddressSplitter;
use Shopware;

require_once 'Components/Api/vendor/autoload.php';

class Util
{

    /**
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
            $swAddress['firstname'],
            $swAddress['lastname'],
            $splitAddress['streetName'],
            $splitAddress['houseNumber'],
            $swAddress['zipcode'],
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

    public function getCTCountryIso3($countryId)
    {
        $countrySql = 'SELECT iso3 FROM s_core_countries WHERE id=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryId]);
    }

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
    public function getUserDoB($user)
    {
        $birthdate = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $birthdate = isset($user['billing']['birthday']) ? $user['billing']['birthday'] : $user['additional']['user']['birthday'] ;
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
    public function getUserPhone($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $billing = $user->getBilling();

        return $billing->getPhone();
    }

    public function getUserSSN($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
          ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        return $attribute->getFatchipctSocialsecuritynumber();
    }

    public function getUserAnnualSalary($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
          ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        return $attribute->getFatchipctAnnualSalary();
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    public function getCountryIdFromAddress($swAddress)
    {
        $countryId = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $countryId = $swAddress['countryId'];
        } else {
            $countryId = $swAddress['countryID'];
        }
        return $countryId;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
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
    public function updateUserPhone($userId, $phone)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $billing = $user->getBilling();
        $billing->setPhone($phone);
        Shopware()->Models()->persist($billing);
        Shopware()->Models()->flush($billing);
    }


    public function updateUserSSN($userId, $ssn)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctSocialsecuritynumber($ssn);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    public function updateUserAnnualSalary($userId, $ssn)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctAnnualSalary($ssn);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
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

    /***
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

    public function needSocialSecurityNumberForKlarna() {
        if ($countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'DK' || $countryIso == 'FI' || $countryIso == 'SE' || $countryIso == 'NO') {
                return true;
            }
        }

        return false;
    }


    public function getSocialSecurityNumberLabelForKlarna($userData) {
        $label = 'Sozialversicherungsnummer (letzte 4 Ziffern)';
        //For comapnies, the field is called Handelsregisternummer
        if (isset($userData['billingaddress']['company'])) {
            $label = 'Handelsregisternummer';
        }
        else if ($countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'NO') {
                $label = 'Sozialversicherungsnummer (letzte 5 Ziffern)';
            }
        }

        return $label;
    }

    /***
     * @param $userData
     * @return bool
     *
     * Annual salary is mandatory for Private Customers in Denmark
     */
    public function needAnnualSalaryForKlarna($userData) {
        if (!isset($userData['billingaddress']['company']) && $countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'DK' ) {
                return true;
            }
        }

        return false;
    }

    public function getSSNLength($userData) {
        //for companies, we do not need a max length
        if (!isset($userData['billingaddress']['company']) && $countryIso = $this->getBillingIsoForCurrentOrder()) {
            if ($countryIso == 'NO') {
                return 5;
            }
            return 4;
        }
        return null;
    }

    private function getBillingIsoForCurrentOrder() {
        if($orderVars = Shopware()->Session()->sOrderVariables) {
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
}
