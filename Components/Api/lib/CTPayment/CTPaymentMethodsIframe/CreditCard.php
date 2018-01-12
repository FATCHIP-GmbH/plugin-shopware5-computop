<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTPaymentMethodIframe;

class CreditCard extends CTPaymentMethodIframe
{
    /**
     * Bestimmt Art und Zeitpunkt der Buchung (engl. Capture).
     * AUTO: Buchung so-fort nach Autorisierung (Standardwert).
     * MANUAL: Buchung erfolgt durch den Händler.
     * <Zahl>: Verzögerung in Stunden bis zur Buchung (ganze Zahl; 1 bis 696).
     *
     * @var string
     */
    protected $capture = 'AUTO';

    /**
     * Name der XSLT-Datei mit Ihrem individuellen Layout für das Bezahlformular.
     * Wenn Sie das neugestaltete und abwärtskompatible Computop-Template nut-zen möchten,
     * übergeben Sie den Templatenamen „ct_compatible“. Wenn Sie das Responsive Computop-Template für mobile Endgeräte
     * nutzen möchten, übergeben Sie den Templatenamen „ct_responsive“.
     * @var string
     */
    protected $Template;

    /**
     * @var
     */
    protected $acquirer;

    /**
     * Ein von Händler zu setzender Wert, um Informationen wieder unverschlüsselt zurückzugeben, zB die MID
     *
     * @var string
     */
    protected $plain;

    /*FIELD FOR AVS*/

    /**
     * Straßenname (für AVS)
     *
     * für GICC und Omnipay ohne hausnummer, für CAPN mit hausnummer
     *
     * @var string
     */
    protected $AddrStreet;

    /**
     * Hausnummer zur Verifizierung durch American Express (für AVS)
     *
     * @var string
     */
    protected $AddrStreetNr;

    /**
     * Postleitzahl (für AVS)
     *
     * @var string
     */
    protected $AddrZip;

    /**
     * Ortsname (für AVS)
     *
     * @var string
     */
    protected $AddrCity;

    /**
     * Code des Bundeslandes des Kunden
     *
     * @var string
     */
    protected $AddrState;

    /**
     * Ländercode im Format ISO-3166-1:
     * er kann wahlweise 2-stellig oder 3-istellig übergeben werden – Format a2 / a3 (für AVS)
     *
     * @var string
     */
    protected  $addrCountryCode;

    /* FIELDS FOR AMEX/CAPN*/
    /**
     * Prepaid-Karte: Tatsächlich autorisierter Betrag in der kleinsten Währungsein-heit.
     *
     * @var int
     */
    protected $AmountAuth;

    /**
     * Vorname des Kunden (für AVS)
     *
     * @var string
     */
    protected $FirstName;

    /**
     * Nachname des Kunden (für AVS)
     *
     * @var string
     */
    protected $LastName;

    /**
     * Vorname in der Lieferadresse (für AVS)
     *
     * @var string
     */
    protected $sdFirstName;

    /**
     * Nachname in der Lieferadresse (für AVS)     *
     *
     * @var string
     */
    protected $sdLastName;

    /**
     * Straßenname und Hausnummer in der Lieferadresse, z.B. 4102~N~289~PL (für AVS)
     *
     * @var string
     */
    protected $sdStreet;

    /**
     * Postleitzahl in der Lieferadresse
     *
     * @var string
     */
    protected $sdZip;

    /**
     * Ländercode der Lieferadresse im Format ISO-3166-1, numerisch 3-stellig (für AVS)
     *
     * @var string
     */

    //TODO: create possiblilty to get country code in 2 or 3 chars
    protected $sdCountryCode;





    public function getCTPaymentURL()
    {
        return 'https://www.computop-paygate.com/payssl.aspx';
    }


    /**
     * @param $config - array, must contain at least 'mac', 'blowfishpass' and 'merchantID'
     * @param CTOrder $order
     * @param $URLSuccess
     * @param $URLFailure
     * @param $URLNotify
     */
    public function __construct(
        $config,
        $order,
        $URLSuccess,
        $URLFailure,
        $URLNotify
    ) {
        parent::__construct($config, $order);

        $this->setURLSuccess($URLSuccess);
        $this->setURLFailure($URLFailure);
        $this->setURLNotify($URLNotify);

        switch($config['creditCardAcquirer']) {
            case 'GICC':
            case 'Omnipay':
                $this->setAddrStreet($order->getBillingAddress()->getStreet());
                $this->setAddrStreetNr($order->getBillingAddress()->getStreetNr());
                $this->setAddrZip($order->getBillingAddress()->getZip());
                $this->setAddrCity($order->getBillingAddress()->getCity());
                $this->setAddrCountryCode($order->getBillingAddress()->getCountryCode());
                $this->setAddrState($order->getBillingAddress()->getState());
                break;
            case 'CAPN':
                $this->setAmountAuth($order->getAmount());
                $this->setFirstName($order->getBillingAddress()->getFirstName());
                $this->setLastName($order->getBillingAddress()->getLastName());
                $this->setAddrStreet($order->getBillingAddress()->getStreet() . ' ' . $order->getBillingAddress()->getStreetNr());
                $this->setAddrZip($order->getBillingAddress()->getZip());
                $this->setSdFirstName($order->getShippingAddress()->getFirstName());
                $this->setSdLastName($order->getShippingAddress()->getLastName());
                $this->setSdStreet($order->getShippingAddress()->getStreet() . ' ' . $order->getShippingAddress()->getStreetNr());
                $this->setSdZip($order->getShippingAddress()->getZip());
                $this->setSdCountryCode($order->getShippingAddress()->getCountryCode());


                break;
        }

        //$this->setUserData($UserData);
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency',
        'MAC', 'URLSuccess', 'URLFailure', 'URLNotify', ));


    }

    /**
     * @param string $capture
     */
    public function setCapture($capture)
    {
        $this->capture = $capture;
    }

    /**
     * @return string
     */
    public function getCapture()
    {
        return $this->capture;
    }

    /**
     * @param string $AddrStreet
     */
    public function setAddrStreet($AddrStreet) {
        $this->AddrStreet = $AddrStreet;
    }

    /**
     * @return string
     */
    public function getAddrStreet() {
        return $this->AddrStreet;
    }

    /**
     * @param string $AddrStreetNr
     */
    public function setAddrStreetNr($AddrStreetNr) {
        $this->AddrStreetNr = $AddrStreetNr;
    }

    /**
     * @return string
     */
    public function getAddrStreetNr() {
        return $this->AddrStreetNr;
    }

    /**
     * @param string $AddrCity
     */
    public function setAddrCity($AddrCity) {
        $this->AddrCity = $AddrCity;
    }

    /**
     * @return string
     */
    public function getAddrCity() {
        return $this->AddrCity;
    }

    /**
     * @param string $AddrZip
     */
    public function setAddrZip($AddrZip) {
        $this->AddrZip = $AddrZip;
    }

    /**
     * @return string
     */
    public function getAddrZip() {
        return $this->AddrZip;
    }

    /**
     * @param string $addrCountryCode
     */
    public function setAddrCountryCode($addrCountryCode) {
        $this->addrCountryCode = $addrCountryCode;
    }

    /**
     * @return string
     */
    public function getAddrCountryCode() {
        return $this->addrCountryCode;
    }

    /**
     * @param string $AddrState
     */
    public function setAddrState($AddrState) {
        $this->AddrState = $AddrState;
    }

    /**
     * @return string
     */
    public function getAddrState() {
        return $this->AddrState;
    }

    /**
     * @param int $AmountAuth
     */
    public function setAmountAuth($AmountAuth) {
        $this->AmountAuth = $AmountAuth;
    }

    /**
     * @return int
     */
    public function getAmountAuth() {
        return $this->AmountAuth;
    }

    /**
     * @param string $FirstName
     */
    public function setFirstName($FirstName) {
        $this->FirstName = $FirstName;
    }

    /**
     * @return string
     */
    public function getFirstName() {
        return $this->FirstName;
    }

    /**
     * @param string $LastName
     */
    public function setLastName($LastName) {
        $this->LastName = $LastName;
    }

    /**
     * @return string
     */
    public function getLastName() {
        return $this->LastName;
    }

    /**
     * @param string $sdZip
     */
    public function setSdZip($sdZip) {
        $this->sdZip = $sdZip;
    }

    /**
     * @return string
     */
    public function getSdZip() {
        return $this->sdZip;
    }

    /**
     * @param string $sdCountryCode
     */
    public function setSdCountryCode($sdCountryCode) {
        $this->sdCountryCode = $sdCountryCode;
    }

    /**
     * @return string
     */
    public function getSdCountryCode() {
        return $this->sdCountryCode;
    }

    /**
     * @param string $sdFirstName
     */
    public function setSdFirstName($sdFirstName) {
        $this->sdFirstName = $sdFirstName;
    }

    /**
     * @return string
     */
    public function getSdFirstName() {
        return $this->sdFirstName;
    }

    /**
     * @param string $sdLastName
     */
    public function setSdLastName($sdLastName) {
        $this->sdLastName = $sdLastName;
    }

    /**
     * @return string
     */
    public function getSdLastName() {
        return $this->sdLastName;
    }

    /**
     * @param string $sdStreet
     */
    public function setSdStreet($sdStreet) {
        $this->sdStreet = $sdStreet;
    }

    /**
     * @return string
     */
    public function getSdStreet() {
        return $this->sdStreet;
    }





    public function getCTRefundURL()
    {
        return 'https://www.computop-paygate.com/credit.aspx';
    }

    public function getSettingsDefinitions()
    {
        return 'Capture, Templateauswahl, Acquirerauswahl';
    }
}
