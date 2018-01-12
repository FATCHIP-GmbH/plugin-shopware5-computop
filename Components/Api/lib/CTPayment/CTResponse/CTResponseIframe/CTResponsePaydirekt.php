<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 04.12.17
 * Time: 11:39
 */

namespace Fatchip\CTPayment\CTResponse\CTResponseIframe;

use Fatchip\CTPayment\CTResponse\CTResponseIframe;

class CTResponsePaydirekt extends CTResponseIframe
{

  /**
   * Eindeutige ID des Vorgangs bei paydirekt
   *
   * @var string
   */
    protected $Reference;

    /**
     * Vorname in der Lieferanschrift
     *
     * @var string
     */
    protected $sdFirstName;

    /**
     * Nachname in der Lieferanschrift
     *
     * @var string
     */
    protected $sdLastName;

    /**
     * Straßenname in der Lieferanschrift
     *
     * @var string
     */
    protected $sdStreet;

    /**
     * Hausnummer in der Lieferanschrift
     *
     * @var string
     */
    protected $sdStreetNr;

    /**
     * Postleitzahl in der Lieferanschrift
     *
     * @var string
     */
    protected $sdZip;

    /**
     * Ort in der Lieferanschrift
     *
     * @var string
     */
    protected $sdCity;

    /**
     * Ländercode in der Lieferanschrift
     *
     * @var string
     */
    protected $sdCountryCode;

    /**
     * E-Mail-Adresse des Empfängers
     *
     * @var string
     */
    protected $sdEmail;

    /**
     * Eindeutige Identifikation des Vorgangs und aller dazugehörigen Transaktionen bei paydirekt.
     * Diese ID ist vorhanden, sobald sich ein Kunde auf der Checkout-Seite eingeloggt hat.
     *
     * @var string
     */
    protected $TID;

    /**
     * @param string $Reference
     */
    public function setReference($Reference)
    {
        $this->Reference = $Reference;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->Reference;
    }

    /**
     * @param string $sdCity
     */
    public function setSdCity($sdCity)
    {
        $this->sdCity = $sdCity;
    }

    /**
     * @return string
     */
    public function getSdCity()
    {
        return $this->sdCity;
    }

    /**
     * @param string $sdCountryCode
     */
    public function setSdCountryCode($sdCountryCode)
    {
        $this->sdCountryCode = $sdCountryCode;
    }

    /**
     * @return string
     */
    public function getSdCountryCode()
    {
        return $this->sdCountryCode;
    }

    /**
     * @param string $sdEmail
     */
    public function setSdEmail($sdEmail)
    {
        $this->sdEmail = $sdEmail;
    }

    /**
     * @return string
     */
    public function getSdEmail()
    {
        return $this->sdEmail;
    }

    /**
     * @param string $sdFirstName
     */
    public function setSdFirstName($sdFirstName)
    {
        $this->sdFirstName = $sdFirstName;
    }

    /**
     * @return string
     */
    public function getSdFirstName()
    {
        return $this->sdFirstName;
    }

    /**
     * @param string $sdLastName
     */
    public function setSdLastName($sdLastName)
    {
        $this->sdLastName = $sdLastName;
    }

    /**
     * @return string
     */
    public function getSdLastName()
    {
        return $this->sdLastName;
    }

    /**
     * @param string $sdStreet
     */
    public function setSdStreet($sdStreet)
    {
        $this->sdStreet = $sdStreet;
    }

    /**
     * @return string
     */
    public function getSdStreet()
    {
        return $this->sdStreet;
    }

    /**
     * @param string $sdStreetNr
     */
    public function setSdStreetNr($sdStreetNr)
    {
        $this->sdStreetNr = $sdStreetNr;
    }

    /**
     * @return string
     */
    public function getSdStreetNr()
    {
        return $this->sdStreetNr;
    }



    /**
     * @param string $sdZip
     */
    public function setSdZip($sdZip)
    {
        $this->sdZip = $sdZip;
    }

    /**
     * @return string
     */
    public function getSdZip()
    {
        return $this->sdZip;
    }

    /**
     * @param string $TID
     */
    public function setTID($TID)
    {
        $this->TID = $TID;
    }

    /**
     * @return string
     */
    public function getTID()
    {
        return $this->TID;
    }
}
