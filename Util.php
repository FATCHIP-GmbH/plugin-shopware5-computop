<?php
/**
 * Created by PhpStorm.
 * User: stefstar
 * Date: 1/12/18
 * Time: 7:28 PM
 */

namespace Shopware\FatchipCTPayment;

use Fatchip\CTPayment\CTAddress\CTAddress;
use VIISON\AddressSplitter\AddressSplitter;
use Shopware;


class Util
{
    /**
     * @param array $swAddress
     * @return CTAddress
     * @throws Exception
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
     * ToDo check if we can reliably get country from user object in all SW versions
     * (SW5.2: user['additional']['country']['countryiso'])
     * (SW5.0-5.1: )
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


    // SW 5.0 - 5.3 Comppatibility
    public function getUserCustomerNumber($user)
    {
        $customerNumber = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $customerNumber = $user['billing']['customernumber'];
        } else {
            $customerNumber =$user['billingaddress']['customernumber'];
        }
        return $customerNumber;
    }

    // SW 5.0 - 5.3 Compatibility
    public function getUserDoB($user)
    {
        $birthdate = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $birthdate = $user['billing']['birthday'];
        } else {
            $birthdate = $user['billingaddress']['birthday'];
        }
        return $birthdate;
    }

    // SW 5.0 - 5.3 Compatibility
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
    public function updateUserDoB($userId, $birthday)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $user->$user->setBirthday($birthday);
            Shopware()->Models()->persist($user);
            Shopware()->Models()->flush($user);

        } else {
            $billing = $user->getBilling();
            $billing->setBirthday($birthday);
            Shopware()->Models()->persist($billing);
            Shopware()->Models()->flush($billing);
        }
    }

    /**
     * returns payment name
     *
     * @param string $paymentID
     * @return string
     */
    public function getPaymentNameFromId($paymentID)
    {
        $sql         = 'SELECT `name` FROM `s_core_paymentmeans` WHERE id = ?';
        return  Shopware()->Db()->fetchOne($sql, $paymentID);
    }
}


