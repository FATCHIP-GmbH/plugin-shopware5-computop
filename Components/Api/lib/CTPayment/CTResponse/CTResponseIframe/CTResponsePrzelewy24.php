<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 04.12.17
 * Time: 12:12
 */

namespace Fatchip\CTPayment\CTResponse\CTResponseIframe;

use Fatchip\CTPayment\CTResponse\CTResponseIframe;

class CTResponsePrzelewy24 extends CTResponseIframe
{
    /**
     * Zahlungszweck
     *
     * @var string
     */
    protected $PaymentPurpose;

    /**
     * Transaktionsnummer bei PostFinance
     *
     * @var string
     */
    protected $TID;

    /**
     * Dieser Parameter wird nur zurückgegeben, falls der Status=OK ist.
     *
     * NONE = keine Zahlungsgarantie
     * VALIDATED = Kundenkonto valide, aber keine Zahlungsgarantie
     * FULL = Zahlungsgarantie Hinweis:
     *
     * @var string
     */
    protected $PaymentGuarantee;

    /**
     * @var
     */
    protected $AccOwner;

    /**
     * Name des Kontoinhabers
     *
     * @var string
     */
    protected $AccNr;

    /**
     * @var string
     */
    protected $AccIBAN;

    /**
     * @var string
     */
    protected $AccBank;

    /**
     * @var string
     */
    protected $IBAN;

    /**
     * @var string
     */
    protected $BIC;

    /**
     * @var string
     */
    protected $TransactionID;

    /**
     * @param string $AccBank
     */
    public function setAccBank($AccBank)
    {
        $this->AccBank = $AccBank;
    }

    /**
     * @return string
     */
    public function getAccBank()
    {
        return $this->AccBank;
    }

    /**
     * @param string $AccIBAN
     */
    public function setAccIBAN($AccIBAN)
    {
        $this->AccIBAN = $AccIBAN;
    }

    /**
     * @return string
     */
    public function getAccIBAN()
    {
        return $this->AccIBAN;
    }

    /**
     * @param string $AccNr
     */
    public function setAccNr($AccNr)
    {
        $this->AccNr = $AccNr;
    }

    /**
     * @return string
     */
    public function getAccNr()
    {
        return $this->AccNr;
    }

    /**
     * @param mixed $AccOwner
     */
    public function setAccOwner($AccOwner)
    {
        $this->AccOwner = $AccOwner;
    }

    /**
     * @return mixed
     */
    public function getAccOwner()
    {
        return $this->AccOwner;
    }

    /**
     * @param string $BIC
     */
    public function setBIC($BIC)
    {
        $this->BIC = $BIC;
    }

    /**
     * @return string
     */
    public function getBIC()
    {
        return $this->BIC;
    }

    /**
     * @param string $IBAN
     */
    public function setIBAN($IBAN)
    {
        $this->IBAN = $IBAN;
    }

    /**
     * @return string
     */
    public function getIBAN()
    {
        return $this->IBAN;
    }

    /**
     * @param string $PaymentGuarantee
     */
    public function setPaymentGuarantee($PaymentGuarantee)
    {
        $this->PaymentGuarantee = $PaymentGuarantee;
    }

    /**
     * @return string
     */
    public function getPaymentGuarantee()
    {
        return $this->PaymentGuarantee;
    }

    /**
     * @param string $PaymentPurpose
     */
    public function setPaymentPurpose($PaymentPurpose)
    {
        $this->PaymentPurpose = $PaymentPurpose;
    }

    /**
     * @return string
     */
    public function getPaymentPurpose()
    {
        return $this->PaymentPurpose;
    }

    /**
     * @param string $TID
     */
    public function setTID($TID)
    {
        $this->TID = $TID;
    }

    /**
     * @return string
     */
    public function getTID()
    {
        return $this->TID;
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
}
