<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTPaymentMethodIframe;

class Sofort extends CTPaymentMethodIframe
{
    /**
     * „Ident“ für Sofort Ident oder „ideal“ für Sofort iDEAL
     * Ident müssen wir nicht implementieren, macht nur identification
     *
     * @var string
     */
    protected $sofortAction = 'ideal';

    /**
     * ID der Bank, über die iDEAL-Überweisungen erfolgen sollen;
     * Pflicht bei So-fortaction=ideal
     * Folgende IssuerIDs sind verfügbar:
     * RABONL2U - Rabobank
     * INGBNL2A - ING
     * ABNANL2A - ABN Amro
     * ASNBNL21 - ASN Bank
     * SNSBNL2A - SNS Bank
     * RBRBNL21 - Regiobank
     * TRIONL2U - Triodos Bank
     * FVLBNL22 - Van Lanschot Bankiers
     * KNABNL2H - Knab
     * BUNQNL2A - bunq
     *
     * @var string
     */
    protected $issuerID; //Bic der ausgewählten Bank

    /**
     * Ländercode zweistellig gemäß ISO 3166.
     * Derzeit DE, AT, BE, NL, ES, CH, PL, IT zulässig.
     *
     * @var string
     */
    protected $AddrCountryCode;

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
        $this->setAddrCountryCode($order->getBillingAddress()->getCountryCode());
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency', 'OrderDesc', 'AddrCountryCode',
          'MAC', 'URLSuccess', 'URLFailure', 'URLNotify', ));
    }


    /**
     * @param string $sofortAction
     */
    public function setSofortAction($sofortAction)
    {
        $this->sofortAction = $sofortAction;
    }

    /**
     * @return string
     */
    public function getSofortAction()
    {
        return $this->sofortAction;
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


    /*protected function getTransactionArray()
    {
        //first get obligitory from parent
        $queryarray =  parent::getTransactionArray();
        // obligatory for Sofort
        $queryarray[] = "AddrCountryCode=" . $this->getAddrCountryCode();
        //Optional for sofort
        if (strlen($this->getSofortAction()) > 0) {
            $queryarray[] = "Sofortaction=" . $this->getSofortAction();
        }
        if (strlen($this->getIssuerID()) > 0) {
            $queryarray[] = "IssuerID=" . $this->getIssuerID();
        }

        return $queryarray;
    }*/

    public function getCTPaymentURL()
    {
        return 'https://www.computop-paygate.com/sofort.aspx';
    }

    public function getCTRefundURL()
    {
        return 'https://www.computop-paygate.com/credit.aspx';
    }

    public function getSettingsDefinitions()
    {
        return 'Sofort oder SofortIdent';
    }
}
