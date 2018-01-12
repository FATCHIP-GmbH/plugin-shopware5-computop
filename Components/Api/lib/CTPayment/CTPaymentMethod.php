<?php

namespace Fatchip\CTPayment;

abstract class CTPaymentMethod extends Blowfish
{


    /**
     * Vom Paygate vergebene ID für die Zahlung. Z.B. zur Referenzierung in Batch-Dateien.
     *
     * @var string
     */
    protected $PayID;


    /**
     * Array of mandatory field names. Has to be set in constructor
     * @var array
     */
    protected $mandatoryFields;

    /**
     * Calculate the MAC value.
     *
     * @param string $PayId
     * @param string $TransID
     * @param string $MerchantID
     * @param integer $Amount
     * @param string $Currency
     * @param string $mac
     * @return string
     */
    abstract protected function ctHMAC($MerchantID, $Amount, $Currency, $mac, $PayId = "", $TransID = "");


    /**
     * @param string $PayID
     */
    public function setPayID($PayID)
    {
        $this->PayID = $PayID;
    }

    /**
     * @return string
     */
    public function getPayID()
    {
        return $this->PayID;
    }


    /**
     * @param array $mandatoryFields
     */
    public function setMandatoryFields($mandatoryFields)
    {
        $this->mandatoryFields = $mandatoryFields;
    }

    /**
     * @return array
     */
    public function getMandatoryFields()
    {
        return $this->mandatoryFields;
    }

    public function getPaymentMethods() {
        return array(
          array(
            'name' => 'fatchip_computop_cc',
            'shortname' => 'Kreditkarte',
            'description' => 'Computop Kreditkarte',
          ),
          array(
            'name' => 'fatchip_computop_easycredit',
            'shortname' => 'Easycredit',
            'description' => 'Computop Easycredit',
          ),
          array(
            'name' => 'fatchip_computop_ideal',
            'shortname' => 'iDEAL',
            'description' => 'Computop iDEAL',
          ),
          array(
            'name' => 'fatchip_computop_klarna',
            'shortname' => 'Klarna',
            'description' => 'Computop Klarna',
          ),
          array(
            'name' => 'fatchip_computop_lastschrift',
            'shortname' => 'Lastschrift',
            'description' => 'Computop Lastschrift',
          ),
          array(
            'name' => 'fatchip_computop_mobilepay',
            'shortname' => 'Mobile Pay',
            'description' => 'Computop Mobile Pay',
          ),
          array(
            'name' => 'fatchip_computop_paydirekt',
            'shortname' => 'Paydirekt',
            'description' => 'Computop Paydirekt',
          ),
          array(
            'name' => 'fatchip_computop_paypal_standard',
            'shortname' => 'PayPal',
            'description' => 'Computop PayPal Standard',
          ),
          array(
            'name' => 'fatchip_computop_postfinance',
            'shortname' => 'Postfinance',
            'description' => 'Computop Postfinance',
          ),
          array(
            'name' => 'fatchip_computop_przelewy24',
            'shortname' => 'Przelewy24',
            'description' => 'Przelewy24',
          ),
          array(
            'name' => 'fatchip_computop_sofort',
            'shortname' => 'SOFORT',
            'description' => 'Computop SOFORT',
          ),
        );
    }
}
