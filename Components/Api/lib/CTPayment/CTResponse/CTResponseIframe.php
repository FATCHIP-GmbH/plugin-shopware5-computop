<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 01.12.17
 * Time: 15:12
 */


namespace Fatchip\CTPayment\CTResponse;

abstract class CTResponseIframe extends CTResponse
{
    /**
     * HändlerID, die von Computop vergeben wird
     *
     * @var string
     */
    protected $MID = null;

    /**
     * Vom Paygate vergebene ID für die Zahlung
     *
     * @var string
     */
    protected $PayID = null;


    /**
     * Vom Paygate vergebene ID für alle einzelnen Transaktionen (Autorisierung, Bu-chung, Gutschrift),
     * die für eine Zahlung durchgeführt werden
     *
     * @var string
     */
    protected $XID = null;


    /**
     * Transaktionsnummer des Händlers
     *
     * @var string
     */
    protected $TransID = null;

    /**
     * @param string $MID
     */
    public function setMID($MID)
    {
        $this->MID = $MID;
    }

    /**
     * @return string
     */
    public function getMID()
    {
        return $this->MID;
    }

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
     * @param string $XID
     */
    public function setXID($XID)
    {
        $this->XID = $XID;
    }

    /**
     * @return string
     */
    public function getXID()
    {
        return $this->XID;
    }

    /**
     * @param string $TransID
     */
    public function setTransID($TransID)
    {
        $this->TransID = $TransID;
    }

    /**
     * @return string
     */
    public function getTransID()
    {
        return $this->TransID;
    }
}
