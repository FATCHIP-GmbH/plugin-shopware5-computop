<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 04.12.17
 * Time: 11:47
 */

namespace Fatchip\CTPayment\CTResponse\CTResponseIframe;

use Fatchip\CTPayment\CTResponse\CTResponseIframe;

class CTResponsePaypalStandard extends CTResponseIframe
{
    /**
     * Eindeutige Transaktionsnummer bei PayPal
     *
     * @var string
     */
    protected $TransactionID;

    /**
     * Nachricht an den Händler
     *
     * @var string
     */
    protected $InfoText;

    /**
     * Vorname und Nachname zusammengenommen
     *
     * @var string
     */
    protected $name;

    /**
     * Vorname vom Bezahler (PayerInfo, kann vom Account-Namen abweichen)
     *
     * @var string
     */
    protected $FirstName;

    /**
     * Nachname vom Bezahler (PayerInfo, kann vom Account-Namen abweichen)
     *
     * @var string
     */
    protected $LastName;

    /**
     * E-Mail-Adresse des Käufers.
     *
     * @var string
     */
    protected $EMail;

    /**
     * Straßenname der Lieferadresse
     *
     * @var string
     */
    protected $AddrStreet;

    /**
     * 2. Straßenname der Lieferadresse, wenn mit Computop abgestimmt
     *
     * @var string
     */
    protected $AddrStreet2;

    /**
     * Ortsname der Lieferadresse
     *
     * @var string
     */
    protected $AddrCity;

    /**
     * Bundesland (Bundesstaat) der Lieferadresse
     *
     * @var string
     */
    protected $AddrState;

    /**
     * Postleitzahl der Lieferadresse
     *
     * @var string
     */
    protected $AddrZIP;

    /**
     * Ländercode des Lieferlandes
     *
     * @var string
     */
    protected $AddrCountryCode;

    /**
     * Identifikationsnummer der Rechnungsvereinbarung. Wenn der Käufer die Rech-nungsvereinbarung bestätigt,
     * wird sie gültig und bleibt gültig, bis sie vom Käufer widerrufen wird.
     *
     * @var string
     */
    protected $BillingAgreementiD;

    /**
     * @param string $AddrCity
     */
    public function setAddrCity($AddrCity)
    {
        $this->AddrCity = $AddrCity;
    }

    /**
     * @return string
     */
    public function getAddrCity()
    {
        return $this->AddrCity;
    }

    /**
     * @param string $AddrCountryCode
     */
    public function setAddrCountryCode($AddrCountryCode)
    {
        $this->AddrCountryCode = $AddrCountryCode;
    }

    /**
     * @return string
     */
    public function getAddrCountryCode()
    {
        return $this->AddrCountryCode;
    }

    /**
     * @param string $AddrState
     */
    public function setAddrState($AddrState)
    {
        $this->AddrState = $AddrState;
    }

    /**
     * @return string
     */
    public function getAddrState()
    {
        return $this->AddrState;
    }

    /**
     * @param string $AddrStreet
     */
    public function setAddrStreet($AddrStreet)
    {
        $this->AddrStreet = $AddrStreet;
    }

    /**
     * @return string
     */
    public function getAddrStreet()
    {
        return $this->AddrStreet;
    }

    /**
     * @param string $AddrStreet2
     */
    public function setAddrStreet2($AddrStreet2)
    {
        $this->AddrStreet2 = $AddrStreet2;
    }

    /**
     * @return string
     */
    public function getAddrStreet2()
    {
        return $this->AddrStreet2;
    }

    /**
     * @param string $AddrZIP
     */
    public function setAddrZIP($AddrZIP)
    {
        $this->AddrZIP = $AddrZIP;
    }

    /**
     * @return string
     */
    public function getAddrZIP()
    {
        return $this->AddrZIP;
    }

    /**
     * @param string $BillingAgreementiD
     */
    public function setBillingAgreementiD($BillingAgreementiD)
    {
        $this->BillingAgreementiD = $BillingAgreementiD;
    }

    /**
     * @return string
     */
    public function getBillingAgreementiD()
    {
        return $this->BillingAgreementiD;
    }

    /**
 * @param string $EMail
 */
    public function setEMail($EMail)
    {
        $this->EMail = $EMail;
    }



    /**
     * @return string
     */
    public function getEMail()
    {
        return $this->EMail;
    }

    /**
     * @param string $FirstName
     */
    public function setFirstName($FirstName)
    {
        $this->FirstName = $FirstName;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->FirstName;
    }

    /**
     * @param string $InfoText
     */
    public function setInfoText($InfoText)
    {
        $this->InfoText = $InfoText;
    }

    /**
     * @return string
     */
    public function getInfoText()
    {
        return $this->InfoText;
    }

    /**
     * @param string $LastName
     */
    public function setLastName($LastName)
    {
        $this->LastName = $LastName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->LastName;
    }

    /**
     * @param string $TransactionID
     */
    public function setTransactionID($TransactionID)
    {
        $this->TransactionID = $TransactionID;
    }

    /**
     * @return string
     */
    public function getTransactionID()
    {
        return $this->TransactionID;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
