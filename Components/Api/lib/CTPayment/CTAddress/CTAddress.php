<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 08.12.17
 * Time: 17:00
 */

namespace Fatchip\CTPayment\CTAddress;

class CTAddress
{
    private $salutation;
    private $firstName;
    private $lastName;
    private $street;
    private $street2;
    private $streetNr;
    private $zip;
    private $city;
    private $state;
    private $countryCode;

    public function __construct(
        $salutation,
        $firstName,
        $lastName,
        $street,
        $streetNr,
        $zip,
        $city,
        $countryCode,
        $street2 = '',
        $state = ''
    ) {
        $this->setSalutation($salutation);
        $this->city = $city;
        $this->countryCode = $countryCode;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->state = $state;
        $this->street = $street;
        $this->street2 = $street2;
        $this->streetNr = $streetNr;
        $this->zip = $zip;
    }




    /**
     * @param mixed $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return mixed
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $countryCocde
     */
    public function setCountryCode($countryCocde)
    {
        $this->countryCode = $countryCocde;
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @param mixed $salutation
     */
    public function setSalutation($salutation) {
        $this->salutation = $salutation;
    }

    /**
     * @return mixed
     */
    public function getSalutation() {
        return $this->salutation;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param mixed $street2
     */
    public function setStreet2($street2)
    {
        $this->street2 = $street2;
    }

    /**
     * @return mixed
     */
    public function getStreet2()
    {
        return $this->street2;
    }

    /**
     * @param mixed $streetNr
     */
    public function setStreetNr($streetNr)
    {
        $this->streetNr = $streetNr;
    }

    /**
     * @return mixed
     */
    public function getStreetNr()
    {
        return $this->streetNr;
    }
}
