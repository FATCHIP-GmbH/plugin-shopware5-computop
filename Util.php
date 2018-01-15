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
    public function getCTCountry($countryId)
    {
        $countrySql = 'SELECT countryiso FROM s_core_countries WHERE id=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryId]);
    }
}


