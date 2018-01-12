<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTPaymentMethodIframe;

class Ideal extends CTPaymentMethodIframe
{


  /**
   *  Ideal kann entweder direkt aufgerufen werden, oder über Sofort. Hierzu soll es setting geben in Backend
   *
   * @var bool
   */
    private $idealDirekt = true;

    /**
     * Nicht bei PPRO: BIC der ausgewählten Bank (siehe Abfrage der hinterlegten iDEAL-Banken)
     *
     *
     * @var string
     */
    protected $issuerID;

    /**
     * @param $amount
     * @param $currency
     * @param $URLSuccess
     * @param $URLFailure
     * @param $URLNotify
     * @param $OrderDesc
     * @param $UserData
     */
    public function __construct(
      $amount,
      $currency,
      $URLSuccess,
      $URLFailure,
      $URLNotify,
      $OrderDesc,
      $UserData
    ) {
        parent::__construct();

        $this->setAmount($amount);
        $this->setCurrency($currency);
        $this->setURLSuccess($URLSuccess);
        $this->setURLFailure($URLFailure);
        $this->setURLNotify($URLNotify);
        $this->setOrderDesc($OrderDesc);
        $this->setUserData($UserData);
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency', 'OrderDesc', 'MAC',
          'URLSuccess', 'URLFailure', 'URLNotify', ));
    }

    /**
     * @param boolean $idealDirekt
     */
    public function setIdealDirekt($idealDirekt)
    {
        $this->idealDirekt = $idealDirekt;
    }

    /**
     * @return boolean
     */
    public function getIdealDirekt()
    {
        return $this->idealDirekt;
    }



    /**
     * @param string $issuerID
     */
    public function setIssuerID($issuerID)
    {
        $this->issuerID = $issuerID;
    }

    /**
     * @return string
     */
    public function getIssuerID()
    {
        return $this->issuerID;
    }

    public function getCTPaymentURL()
    {
        if ($this->idealDirekt) {
            return 'https://www.computop-paygate.com/ideal.aspx';
        } else {
            return 'https://www.computop-paygate.com/sofort.aspx';
        }
    }

    public function getCTRefundURL()
    {
        return 'https://www.computop-paygate.com/credit.aspx';
    }

    public function getIssuerListURL()
    {
        $queryarray = array();
        $queryarray[] = 'MerchantID=' . $this->getMerchantID();

        $query = join("&", $queryarray);
        $Len = strlen($query);


        $Len = strlen($query);  // Length of the plain text string
        $data = $this->ctEncrypt($query, $Len, $this->getBlowfishPassword());

        return 'https://www.computop-paygate.com/idealIssuerList.aspx' .  '?MerchantID=' . $this->getMerchantID() . '&Len=' . $Len . "&Data=" . $data;
        ;
    }

    public function getSettingsDefinitions()
    {
        return 'Idealdirekt oder Sofort';
    }
}
