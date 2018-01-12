<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTAddress\CTAddress;
use Fatchip\CTPayment\CTPaymentMethodIframe;

class PaypalStandard extends CTPaymentMethodIframe
{
    /**
     * Auto oder Manual: bestimmt, ob der angefragte Betrag sofort oder erst später abgebucht wird.
     * Wichtiger Hinweis: Bitte kontaktieren Sie den Computop Support für Manual,
     * um die unterschiedlichen Einsatzmöglichkeiten abzuklären.
     *
     * @var string
     */
    protected $capture;

    /**
     * Pflicht bei Capture=Manual:
     * Transaktionstyp mit den möglichen Werten Order oder Auth sowie BAID (BillingAgreementID)
     *
     * @var string
     */
    protected $TxType;

    /**
     * optional, plficht für USA und Canada:
     * Entweder nur der Vorname oder Vor- und Nach-name, falls ein Firmenname als Lieferadresse genutzt wird.
     *
     * @var string
     */
    protected $FirstName;

    /**
     * optional, plficht für USA und Canada: Nachname oder Firmenbezeichnung der Lieferad-resse
     *
     * @var string
     */
    protected $LastName;

    /**
     * optional, plficht für USA und Canada: Straßenname der Lieferadresse
     * @var string
     */
    protected $AddrStreet;

    /**
     * optional: Straßenname der Lieferadresse
     *
     * @var string
     */
    protected $AddrStreet2;

    /**
     * optional, plficht für USA und Canada:
     * Ortsname der Lieferadresse
     *
     * @var string
     */
    protected $AddrCity;

    /**
     * optional, plficht für USA und Canada:
     * Bundesland (Bundesstaat) der Lieferadresse. Die in AddrCity übergebene Stadt muss im angegebenen Bundesstaat
     * liegen, sonst lehnt PayPal die Zahlung ab.
     * Mögliche Werte entnehmen Sie bitte der PayPal-API-Reference Dokumentation.
     *
     * @var string
     */
    protected $AddrState;

    /**
     * optional, plficht für USA und Canada:
     * Postleitzahl der Lieferadresse
     *
     * @var string
     */
    protected $AddrZip;

    /**
     * optional, plficht für USA und Canada:
     * Ländercode des Lieferlandes (2stellig)
     *
     * @var string
     */
    protected $AddrCountryCode;


    /**
     * @param $config
     * @param \Fatchip\CTPayment\CTOrder $order
     * @param $URLSuccess
     * @param $URLFailure
     * @param $URLNotify
     * @param $OrderDesc
     * @param $UserData
     * @param $capture
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
        $this->setShippingAddress($order->getShippingAddress());
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency', 'OrderDesc', 'MAC',
          'URLSuccess', 'URLFailure', ));
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
     * @param string $TxType
     */
    public function setTxType($TxType)
    {
        $this->TxType = $TxType;
    }

    /**
     * @return string
     */
    public function getTxType()
    {
        return $this->TxType;
    }

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
     * @param string $AddrZip
     */
    public function setAddrZip($AddrZip)
    {
        $this->AddrZip = $AddrZip;
    }

    /**
     * @return string
     */
    public function getAddrZip()
    {
        return $this->AddrZip;
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
     * @param $shippingAddress CTAddress
     */
    public function setShippingAddress($shippingAddress)
    {
        $this->setFirstName($shippingAddress->getFirstName());
        $this->setLastName($shippingAddress->getLastName());
        if (strlen($shippingAddress->getStreetNr() > 0)) {
            $this->setAddrStreet($shippingAddress->getStreet() . ' ' . $shippingAddress->getStreetNr());
        } else {
            $this->setAddrStreet($shippingAddress->getStreet());
        }

        $this->setAddrZip($shippingAddress->getZip());
        $this->setAddrCity($shippingAddress->getCity());
        $this->setAddrCountryCode($shippingAddress->getCountryCode());
    }


    public function getCTPaymentURL()
    {
        return 'https://www.computop-paygate.com/paypal.aspx';
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
        return 'Capture (2 ausprägungen)';
    }
}
