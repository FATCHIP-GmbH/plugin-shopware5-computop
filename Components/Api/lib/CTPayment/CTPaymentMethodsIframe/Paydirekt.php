<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTAddress\CTAddress;
use Fatchip\CTPayment\CTPaymentMethodIframe;

class Paydirekt extends CTPaymentMethodIframe
{
    /**
     * Bestimmt Art und Zeitpunkt der Buchung (engl. Capture).
     * AUTO: Buchung so-fort nach der Autorisierung (Standardwert).
     * MANUAL: Buchung erfolgt durch den Händler.
     * <Zahl>: Verzögerung in Stunden bis zur Buchung (ganze Zahl; 1 bis 696).
     *
     * @var string
     */
    protected $capture;

    /**
     * API-Key des Shops bei paydirekt
     *
     * @var string
     */
    protected $ShopApiKey;
    /**
     * Warenwert der Bestellung ohne Versandkosten in der kleinsten Währungsein-heit (z.B. EUR Cent)
     * Bitte wenden Sie sich an den Helpdesk, wenn Sie Beträge < 100 (kleinste Wäh-rungseinheit) buchen möchten.
     *
     * @var int
     */
    protected $ShoppingBasketAmount;

    /**
     * Vorname in der Lieferanschrift
     * Pflicht, wenn ShoppingBasketCategory <> „AU-THORITIES_PAYMENT“ und <> „ANONYMOUS_DONATION“
     *
     * @var string
     */
    protected $sdFirstName;

    /**
     * Nachname in der Lieferanschrift.
     * Pflicht, wenn ShoppingBasketCategory <> „AUTHORITIES_PAYMENT“ und <> „ANONYMOUS_DONATION“
     *
     * @var string
     */
    protected $sdLastName;

    /**
     * Optional. Straßenname in der Lieferanschrift
     *
     * @var string
     */
    protected $sdStreet;

    /**
     * Optional: Hausnummer in der Lieferanschrift
     * @var string
     */

    protected $sdStreetNr;

    /**
     * Postleitzahl in der Lieferanschrift
     * Pflicht, wenn ShoppingBasketCategory <> "DIGITAL" und <> "AUTHORI-TIES_PAYMENT" und <> "ANONYMOUS_DONATION"
     *
     * @var int
     */
    protected $sdZip;

    /**
     * Ort in der Lieferanschrift #
     * Pflicht, wenn ShoppingBasketCategory <> "DIGITAL" und <> "AUTHORI-TIES_PAYMENT" und <> "ANONYMOUS_DONATION"
     *
     * @var string
     */
    protected $sdCity;

    /**
     * Ländercode in der Lieferanschrift (2stellig)
     * Pflicht, wenn ShoppingBasketCategory <> "DIGITAL" und <> "AUTHORI-TIES_PAYMENT" und <> "ANONYMOUS_DONATION"
     *
     * @var string
     */
    protected $sdCountryCode;

    /**
     * E-Mail-Adresse des Empfängers
     * Pflicht, wenn ShoppingBasketCategory = „DIGITAL“
     *
     * @var string
     */
    protected $sdEmail;

    /**
     * @param $amount
     * @param $currency
     * @param $URLSuccess
     * @param $URLFailure
     * @param $URLNotify
     * @param $OrderDesc
     * @param $UserData
     * @param $capture
     */
    public function __construct(
      $amount,
      $currency,
      $URLSuccess,
      $URLFailure,
      $URLNotify,
      $OrderDesc,
      $UserData,
      $capture,
      $shopApiKey
    ) {
        parent::__construct();

        $this->setAmount($amount);
        $this->setCurrency($currency);
        $this->setURLSuccess($URLSuccess);
        $this->setURLFailure($URLFailure);
        $this->setURLNotify($URLNotify);
        $this->setOrderDesc($OrderDesc);
        $this->setUserData($UserData);
        $this->setCapture($capture);
        $this->setShopApiKey($shopApiKey);
        //For Paydirekt, the transID has a max length of 20
        $this->TransID = substr($this->TransID, 0, 20);
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency', 'MAC',
          'URLSuccess', 'URLFailure', 'ShopApiKey' ));
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
     * @param string $shopAPIKey
     */
    public function setShopApiKey($shopAPIKey)
    {
        $this->ShopApiKey = $shopAPIKey;
    }

    /**
     * @return string
     */
    public function getShopApiKey()
    {
        return $this->ShopApiKey;
    }

    /**
     * @param int $ShoppingBasketAmount
     */
    public function setShoppingBasketAmount($ShoppingBasketAmount)
    {
        $this->ShoppingBasketAmount = $ShoppingBasketAmount;
    }

    /**
     * @return int
     */
    public function getShoppingBasketAmount()
    {
        return $this->ShoppingBasketAmount;
    }



    /**
     * @param int $sdZip
     */
    public function setZip($sdZip)
    {
        $this->sdZip = $sdZip;
    }

    /**
     * @return int
     */
    public function getZip()
    {
        return $this->sdZip;
    }

    /**
     * @param string $sdCity
     */
    public function setCity($sdCity)
    {
        $this->sdCity = $sdCity;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->sdCity;
    }

    /**
     * @param string $sdCountryCode
     */
    public function setCountryCode($sdCountryCode)
    {
        $this->sdCountryCode = $sdCountryCode;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->sdCountryCode;
    }

    /**
     * @param string $sdEmail
     */
    public function setEmail($sdEmail)
    {
        $this->sdEmail = $sdEmail;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->sdEmail;
    }

    /**
     * @param string $sdFirstName
     */
    public function setFirstName($sdFirstName)
    {
        $this->sdFirstName = $sdFirstName;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->sdFirstName;
    }

    /**
     * @param string $sdLastName
     */
    public function setLastName($sdLastName)
    {
        $this->sdLastName = $sdLastName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->sdLastName;
    }

    /**
     * @param string $sdStreet
     */
    public function setStreet($sdStreet)
    {
        $this->sdStreet = $sdStreet;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->sdStreet;
    }

    /**
     * @param string $sdsdStreetNr
     */
    public function setStreetNr($sdsdStreetNr)
    {
        $this->sdStreetNr = $sdsdStreetNr;
    }

    /**
     * @return string
     */
    public function getStreetNr()
    {
        return $this->sdStreetNr;
    }

    /**
     * @param $DeliveryAddress CTAddress
     */
    public function setDeliveryAddress($DeliveryAddress)
    {
        $this->setFirstName($DeliveryAddress->getFirstName());
        $this->setLastName($DeliveryAddress->getLastName());
        $this->setStreet($DeliveryAddress->getStreet());
        $this->setStreetNr($DeliveryAddress->getStreetNr());
        $this->setZip($DeliveryAddress->getZip());
        $this->setCity($DeliveryAddress->getCity());
        $this->setCountryCode($DeliveryAddress->getCountryCode());
    }

    public function getCTPaymentURL()
    {
        return 'https://www.computop-paygate.com/paydirekt.aspx';
    }

    public function getCTRefundURL()
    {
        return 'https://www.computop-paygate.com/credit.aspx';
    }

    public function getCaptureURL()
    {
        return 'https://www.computop-paygate.com/capture.aspx';
    }

    public function getReverseURL()
    {
        return 'https://www.computop-paygate.com/reverse.aspx';
    }


    public function getSettingsDefinitions()
    {
        return 'Capture (3 ausprägungen), ShopApiKey';
    }
}
