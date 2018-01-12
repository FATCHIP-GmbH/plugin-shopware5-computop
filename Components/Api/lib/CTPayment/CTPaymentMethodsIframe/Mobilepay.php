<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTPaymentMethodIframe;

class Mobilepay extends CTPaymentMethodIframe
{
    /**
     * Der param MobielNr kann, sofern bekannt aus dem Kundenkonto vorbelegt werden.
     * Diese option sollte aber im Backend auf aktiv bzw inaktiv gestellt werden können.
     * @var bool
     */
    protected $sendMobileNumber = false;

    /**
     * Sprache, in der das Mobilepay-Formular angezeigt werden soll.
     * Mögliche Werte: da, no, fi
     *
     * @var string
     */
    protected $language;

    /**
     * //Telefonnummer des Mobilepay-Accounts im Format +4595000012.
     * Der Parameter <MobileNr> kann, sofern bekannt aus dem Kundenkonto vorbelegt wreden.
     * Diese Option sollte aber im Backend auf aktiv bzw. inaktiv gestellt werden können.
     *
     * @var string
     */
    protected $MobileNr;

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
      $config,
      $order,
      $URLSuccess,
      $URLFailure,
      $URLNotify,
      $OrderDesc,
      $UserData
    ) {
        parent::__construct($config, $order);
        $this->setURLSuccess($URLSuccess);
        $this->setURLFailure($URLFailure);
        $this->setURLNotify($URLNotify);
        $this->setOrderDesc($OrderDesc);
        $this->setUserData($UserData);
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency', 'MAC', 'OrderDesc',
          'URLSuccess', 'URLFailure', 'URLNotify', ));
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
     * @param boolean $sendMobileNumber
     */
    public function setSendMobileNumber($sendMobileNumber)
    {
        $this->sendMobileNumber = $sendMobileNumber;
    }

    /**
     * @return boolean
     */
    public function getSendMobileNumber()
    {
        return $this->sendMobileNumber;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    protected function getTransactionArray()
    {
        //first get obligitory from parent
        $queryarray =  parent::getTransactionArray();
        //Optional for mobilepay
        if (strlen($this->getLanguage()) > 0) {
            $queryarray[] = "Language=" . $this->getLanguage();
        }
        if ($this->getSendMobileNumber() && strlen($this->getMobileNr()) > 0) {
            $queryarray[] = "MobileNr=" . $this->getMobileNr();
        }
        return $queryarray;
    }


    public function getCTPaymentURL()
    {
        return 'https://www.computop-paygate.com/MobilePayDB.aspx';
    }

    public function getCTRefundURL()
    {
        return 'https://www.computop-paygate.com/credit.aspx';
    }

    public function getCTCaptureURL()
    {
        return 'https://www.computop-paygate.com/capture.aspx';
    }

    public function getSettingsDefinitions()
    {
        return 'SendMobileNr';
    }
}
