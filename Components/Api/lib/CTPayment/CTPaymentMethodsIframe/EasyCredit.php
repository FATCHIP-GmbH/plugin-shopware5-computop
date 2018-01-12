<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTPaymentMethodIframe;
use Fatchip\CTPayment\CTAddress\CTAddress;
use Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseEasyCredit;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Exception;

class EasyCredit extends CTPaymentMethodIframe
{
    /**
     * Definiert die bei easyCredit auszuführende Anfrage: <INT> zur Initialisierung eines Vorgangs
     *
     * @var
     */
    protected $EventToken;

    /**
     * Anrede HERR oder FRAU
     *
     * @var string
     */
    protected $Salutation;

    /**
     * Vorname
     *
     * @var string
     */
    protected $FirstName;

    /**
     * Nachname
     *
     * @var string
     */
    protected $LastName;

    /**
     * Geburtsdatum im Format YYYY-MM-DD
     *
     * @var string
     */
    protected $DateOfBirth;

    //Billingaddress
    /**
     * Straße
     *
     * @var string
     */
    protected $bdStreet;

    /**
     * Hausnummer
     *
     * @var string
     */
    protected $bdStreetNr;

    /**
     * Adresszusatz
     *
     * @var string
     */
    protected $bdAddressAddition;

    /**
     * Postleitzahl
     *
     * @var int
     */
    protected $bdZip;

    /**
     * Stadt
     *
     * @var string
     */
    protected $bdCity;


    /**
     * Ländercode in der Rechnungsadresse gemäß ISO 3166, zweistellig. Derzeit ist nur DE erlaubt.
     *
     * @var string
     */
    protected $bdCountryCode;

    //Shippingaddress
    /**
     * Packstation
     *
     * @var string
     */
    protected $PackingStation;

    /**
     * Straße
     *
     * @var string
     */
    protected $sdStreet;

    /**
     * Hausnummer
     *
     * @var string
     */
    protected $sdStreetNr;

    /**
     * Adresszusatz
     * @var string
     */
    protected $sdAddressAddition;

    /**
     * Postleitzahl
     *
     * @var int
     */
    protected $sdZip;

    /**
     * Stadt
     *
     * @var string
     */
    protected $sdCity;

    /**
     * Ländercode in der Lieferdresse gemäß ISO 3166, zweistellig. Derzeit ist nur DE erlaubt.
     *
     * @var string
     */
    protected $sdCountryCode;

    //Kontaktdaten
    /**
     * E-Mail-Adresse des Kunden
     *
     * @var string
     */
    protected $Email;

    /**
     * Mobiltelefonnummer des Kunden
     *
     * @var string
     */
    protected $MobileNr;

    /**
     * @param string $Email
     */
    public function setEmail($Email)
    {
        $this->Email = $Email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->Email;
    }

    /**
     * @param mixed $EventToken
     */
    public function setEventToken($EventToken)
    {
        $this->EventToken = $EventToken;
    }

    /**
     * @return mixed
     */
    public function getEventToken()
    {
        return $this->EventToken;
    }

    /**
     * Geburtsdatum im Format YYYY-MM-DD
     *
     * @param string $DateOfBirth
     */
    public function setDateOfBirth($DateOfBirth)
    {
        $this->DateOfBirth = $DateOfBirth;
    }

    /**
     * @return string
     */
    public function getDateOfBirth()
    {
        return $this->DateOfBirth;
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
     * @param string $Salutation
     */
    public function setSalutation($Salutation)
    {
        $this->Salutation = $Salutation;
    }

    /**
     * @return string
     */
    public function getSalutation()
    {
        return $this->Salutation;
    }


    /**
     * @param string $MobileNr
     */
    public function setMobileNr($MobileNr)
    {
        $this->MobileNr = $MobileNr;
    }

    /**
     * @return string
     */
    public function getMobileNr()
    {
        return $this->MobileNr;
    }

    /**
     * @param string $PackingStation
     */
    public function setPackingStation($PackingStation)
    {
        $this->PackingStation = $PackingStation;
    }

    /**
     * @return string
     */
    public function getPackingStation()
    {
        return $this->PackingStation;
    }

    /**
     * @param string $bdAddressAddition
     */
    public function setBdAddressAddition($bdAddressAddition)
    {
        $this->bdAddressAddition = $bdAddressAddition;
    }

    /**
     * @return string
     */
    public function getBdAddressAddition()
    {
        return $this->bdAddressAddition;
    }

    /**
     * @param string $bdCity
     */
    public function setBdCity($bdCity)
    {
        $this->bdCity = $bdCity;
    }

    /**
     * @return string
     */
    public function getBdCity()
    {
        return $this->bdCity;
    }

    /**
     * @param string $bdCountryCode
     */
    public function setBdCountryCode($bdCountryCode)
    {
        $this->bdCountryCode = $bdCountryCode;
    }

    /**
     * @return string
     */
    public function getBdCountryCode()
    {
        return $this->bdCountryCode;
    }

    /**
     * @param string $bdStreet
     */
    public function setBdStreet($bdStreet)
    {
        $this->bdStreet = $bdStreet;
    }

    /**
     * @return string
     */
    public function getBdStreet()
    {
        return $this->bdStreet;
    }

    /**
     * @param string $bdStreetNr
     */
    public function setBdStreetNr($bdStreetNr)
    {
        $this->bdStreetNr = $bdStreetNr;
    }

    /**
     * @return string
     */
    public function getBdStreetNr()
    {
        return $this->bdStreetNr;
    }

    /**
     * @param int $bdZip
     */
    public function setBdZip($bdZip)
    {
        $this->bdZip = $bdZip;
    }

    /**
     * @return int
     */
    public function getBdZip()
    {
        return $this->bdZip;
    }

    /**
     * @param string $sdAddressAddition
     */
    public function setSdAddressAddition($sdAddressAddition)
    {
        $this->sdAddressAddition = $sdAddressAddition;
    }

    /**
     * @return string
     */
    public function getSdAddressAddition()
    {
        return $this->sdAddressAddition;
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
     * @param int $sdZip
     */
    public function setSdZip($sdZip)
    {
        $this->sdZip = $sdZip;
    }

    /**
     * @return int
     */
    public function getSdZip()
    {
        return $this->sdZip;
    }

    /**
     * @param $config
     * @param CTOrder $order
     * @param $URLSuccess
     * @param $URLFailure
     * @param $URLNotify
     * @param $EventToken
     */
    public function __construct(
      $config,
      $order,
      $URLSuccess,
      $URLFailure,
      $URLNotify,
      $EventToken
    ) {
        parent::__construct($config, $order);

        $this->setURLSuccess($URLSuccess);
        $this->setURLFailure($URLFailure);
        $this->setURLNotify($URLNotify);

        $this->setShippingAddress($order->getShippingAddress());
        $this->setBillingAddress($order->getBillingAddress());

        if ($order->getBillingAddress()) {
            $this->setFirstName($order->getBillingAddress()->getFirstName());
            $this->setLastName($order->getBillingAddress()->getLastName());
            $this->setSalutation($order->getBillingAddress()->getSalutation());
        }


        $this->setEventToken($EventToken);
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency',
          'EventToken', 'URLSuccess', 'URLFailure', 'URLNotify', ));
    }


    /**
     * @param $shippingAddress CTAddress
     */
    public function setShippingAddress($shippingAddress)
    {
        if (isset($shippingAddress)) {
            $this->setSdStreet($shippingAddress->getStreet());
            $this->setSdStreetNr($shippingAddress->getStreetNr());
            $this->setSdZip($shippingAddress->getZip());
            $this->setSdCity($shippingAddress->getCity());
            $this->setSdCountryCode($shippingAddress->getCountryCode());
        }
    }

    /**
     * @param $billingAddress CTAddress
     */
    public function setBillingAddress($billingAddress)
    {
        if (isset($billingAddress)) {
            $this->setBdStreet($billingAddress->getStreet());
            $this->setBdStreetNr($billingAddress->getStreetNr());
            $this->setBdZip($billingAddress->getZip());
            $this->setBdCity($billingAddress->getCity());
            $this->setBdCountryCode($billingAddress->getCountryCode());
            $this->setSalutation($billingAddress->getSalutation());
        }
    }

    public function getCTPaymentURL()
    {
        return 'https://www.computop-paygate.com/easyCredit.aspx';
    }

    public function getCTRefundURL()
    {
        return 'https://www.computop-paygate.com/credit.aspx';
    }

    public function getSettingsDefinitions()
    {
        return null;
    }

    /**
     * @param $payID
     * @return CTResponseEasyCredit
     */
    public function getDecision($payID)
    {
        return $this->callEasyCreditDirect($payID, 'GET');
    }

    public function confirm($payID)
    {
        return $this->callEasyCreditDirect($payID, 'CON');
    }

    /**
     * @param $payID
     * @param $EventToken
     * @return CTResponseEasyCredit
     */
    private function callEasyCreditDirect($payID, $EventToken)
    {
        $this->setPayID($payID);
        $this->setEventToken($EventToken);
        $query = $this->getTransactionQuery();
        $Len = strlen($query);
        $data = $this->getEncryptedData();
        $url = 'https://www.computop-paygate.com/easyCreditDirect.aspx' . '?MerchantID=' . $this->getMerchantID() . '&Len=' . $Len . "&Data=" . $data;
        ;

        try {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_URL => $url,
        ));
        $resp = curl_exec($curl);

        if (FALSE === $resp)
            throw new Exception(curl_error($curl), curl_errno($curl));

        } catch(\Exception $e) {
            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()),
              E_USER_ERROR);
        }

        $arr = array();
        parse_str($resp, $arr);
        $respObj = new CTResponseEasyCredit($arr);

        return $respObj;
    }
}
