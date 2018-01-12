<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 04.12.17
 * Time: 11:14
 */

namespace Fatchip\CTPayment\CTResponse\CTResponseIframe;

use Fatchip\CTPayment\CTResponse\CTResponseIframe;

class CTResponseEasyCredit extends CTResponseIframe
{

    /**
     * @var string
     */
    protected $Desicion;

    /**
     * @var string
     */
    protected $Process;

    /**
     * @var string
     */
    protected $Financing;

    /**
     * Eindeutige Referenznummer (Optional)
     *
     * @var string
     */
    protected $RefNr;

    /**
     * @param string $Desicion
     */
    public function setDesicion($Desicion)
    {
        $this->Desicion = $Desicion;
    }

    /**
     * Enthält die Entscheidungsdaten zur vorherigen Initialisie-rung.
     * Diese werden im JSON-Format und Base64-encodiert zurückgegeben     *
     *
     * @return string
     */
    public function getDesicion()
    {
        return  base64_decode($this->Desicion);
    }

    /**
     * @param string $Financing
     */
    public function setFinancing($Financing)
    {
        $this->Financing = $Financing;
    }

    /**
     * @return string
     */
    public function getFinancing()
    {
        return base64_decode($this->Financing);
    }

    /**
     * @param string $Process
     */
    public function setProcess($Process)
    {
        $this->Process = $Process;
    }

    /**
     * @return string
     */
    public function getProcess()
    {
        return base64_decode($this->Process);
    }

    /**
     * @param string $RefNr
     */
    public function setRefNr($RefNr)
    {
        $this->RefNr = $RefNr;
    }

    /**
     * @return string
     */
    public function getRefNr()
    {
        return $this->RefNr;
    }



    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }
}
