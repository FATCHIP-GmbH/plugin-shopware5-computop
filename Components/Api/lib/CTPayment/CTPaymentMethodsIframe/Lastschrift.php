<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTPaymentMethodIframe;

abstract class Lastschrift extends CTPaymentMethodIframe
{

  /**
   * Name der XSLT-Datei mit Ihrem individuellen Layout für das Bezahlformular.
   * Wenn Sie das neugestaltete und abwärtskompatible Computop-Template nut-zen möchten,
   * übergeben Sie den Templatenamen „ct_compatible“.
   * Wenn Sie das Responsive Computop-Template für mobile Endgeräte nutzen möchten,
   * übergeben Sie den Templatenamen „ct_responsive“.
   *
   * @var string
   */
    private $Template;

    /**
     * Bestimmt Art und Zeitpunkt der Buchung (engl. Capture).
     * AUTO: Buchung so-fort nach Autorisierung (Standardwert).
     * MANUAL: Buchung erfolgt durch den Händler.
     * <Zahl>: Verzögerung in Stunden bis zur Buchung (ganze Zahl; 1 bis 696).
     *
     * @var string
     */
    private $capture; //AUTO, MANUAL, ZAHL

    /**
     * Über welchen Dienst wird Lastschrift angebunden`:
     * Direktanbindung
     * EVO Payments
     * Intercard
     *
     * @var
     */
    private $dienst;

    /**
     * für SEPA: SEPA-Mandatsnummer (Pflicht bei SEPA) sollte eindeutig sein, ist nicht case-sensitive
     *
     * @var string
     */
    private $mandateID;

    /**
     * für SEPA: Datum der Mandatserteilung im Format DD.MM.YYYY
     * Pflicht bei Übergabe von MandateID
     *
     * @var string
     */
    private $DtOfSgntr;

    public function __construct(
      $config,
      $order,
      $URLSuccess,
      $URLFailure,
      $URLNotify,
      $OrderDesc,
      $UserData,
      $capture
    ) {
        parent::__construct($config, $order);


        $this->setURLSuccess($URLSuccess);
        $this->setURLFailure($URLFailure);
        $this->setURLNotify($URLNotify);
        $this->setOrderDesc($OrderDesc);
        $this->setUserData($UserData);
        $this->setCapture($capture);
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency', 'MAC', 'OrderDesc',
          'URLSuccess', 'URLFailure', 'URLNotify', ));

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
     * @param mixed $dienst
     */
    public function setDienst($dienst)
    {
        $this->dienst = $dienst;
    }

    /**
     * @return mixed
     */
    public function getDienst()
    {
        return $this->dienst;
    }

    /**
     * @param string $Template
     */
    public function setTemplate($Template)
    {
        $this->Template = $Template;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->Template;
    }

    /**
     * @param string $mandateID
     */
    public function setMandateID($mandateID)
    {
        $this->mandateID = $mandateID;
        //if we set MandateID, also DtOfSgntr is obligatory
        $this->setDtOfSgntr(date('d-m-Y'));
    }

    /**
     * @return string
     */
    public function getMandateID()
    {
        return $this->mandateID;
    }

    /**
     * @param string $DtOfSgntr
     */
    public function setDtOfSgntr($DtOfSgntr)
    {
        $this->DtOfSgntr = $DtOfSgntr;
    }

    /**
     * @return string
     */
    public function getDtOfSgntr()
    {
        return $this->DtOfSgntr;
    }


    /**
     * Each ELV payment needs a unique mandateID.
     * For now, it is the ordernumber plus date
     * @param $orderID
     * @return string
     */
    public function createMandateID($orderID)
    {
        return $orderID . date('yzGis');
    }

    protected function getTransactionArray()
    {
        //first get obligitory from parent
        $queryarray =  parent::getTransactionArray();

        $queryarray[] = "Capture=" . $this->getCapture();

        if (strlen($this->getMandateID()) > 0) {
            $queryarray[] = "MandateID=" . $this->getMandateID();
        }

        if (strlen($this->getDtOfSgntr()) > 0) {
            $queryarray[] = "DtOfSgntr=" . $this->getDtOfSgntr();
        }

        return $queryarray;
    }

    public function getCTPaymentURL()
    {
        return 'https://www.computop-paygate.com/paysdd.aspx';
    }

    public function getCTRefundURL()
    {
        return 'https://www.computop-paygate.com/credit.aspx';
    }

    public function getSettingsDefinitions()
    {
        return 'Welcher dienst wird benutzt';
    }
}
