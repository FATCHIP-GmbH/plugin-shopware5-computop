<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 01.12.17
 * Time: 14:58
 */

namespace Fatchip\CTPayment\CTResponse;

abstract class CTResponse
{

  /**
   * OK oder AUTHORIZED (URLSuccess) sowie FAILED (URLFailure)
   *
   * @var string
   */
    protected $status = null;

    /**
     * @var string
     */
    protected $rawResponse = null;

    /**
     * Nähere Beschreibung bei Ablehnung der Zahlung.
     * Bitte nutzen Sie nicht den Parameter Description sondern Code für die Auswertung des Transaktionssta-tus!
     *
     * @var string
     */
    protected $Description = null;


    /**
     * Fehlercode gemäß Excel-Datei Paygate Antwort Codes
     *
     * @var int
     */
    protected $Code = null;


    /**
     * Hash Message Authentication Code (HMAC) mit SHA-256-Algorithmus
     *
     * @var string
     */
    protected $MAC = null;


    /**
     * Wenn beim Aufruf angegeben, übergibt das Paygate die Parameter mit dem Zahlungsergebnis an den Shop
     *
     * @var string
     */
    protected $UserData;



    /**
     * @param array $params
     */
    public function __construct(array $params = array())
    {
        if (count($params) > 0) {
            $this->init($params);
        }
    }

    /**
     * @param array $data
     */
    public function init(array $data = array())
    {
        foreach ($data as $key => $value) {
            $key = ucwords(str_replace('_', ' ', $key));
            $method = 'set' . str_replace(' ', '', str_replace('-', '', $key));


            if (method_exists($this, $method)) {
                $this->{$method}($value);
            } else {
                $debug = 1;
            }
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = array();
        foreach ($this as $key => $data) {
            if ($data === null) {
                continue;
            } else {
                $result[$key] = $data;
            }
        }

        return $result;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $Code
     */
    public function setCode($Code)
    {
        $this->Code = $Code;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->Code;
    }

    /**
     * @param string $Description
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * @param string $MAC
     */
    public function setMAC($MAC)
    {
        $this->MAC = $MAC;
    }

    /**
     * @return string
     */
    public function getMAC()
    {
        return $this->MAC;
    }

    /**
     * @param string $UserData
     */
    public function setUserData($UserData)
    {
        $this->UserData = $UserData;
    }

    /**
     * @return string
     */
    public function getUserData()
    {
        return $this->UserData;
    }




    /**
     * @param string $key
     * @return null|mixed
     */
    public function getValue($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param string $name
     * @return boolean|null
     */
    public function setValue($key, $name)
    {
        return $this->set($key, $name);
    }

    /**
     * @param $name
     * @return null|mixed
     */
    protected function get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return boolean|null
     */
    protected function set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
            return true;
        }
        return null;
    }

    /**
     * @param $rawResponse
     */
    public function setRawResponse($rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }
    /**
     * @return null
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    public function render()
    {
        $arr = $this->toArray();
        $arr = get_object_vars($this);

        $result = '<table>';
        foreach ($arr as $name => $value) {
            $result .= '<tr><td>' . $name . '</td><td>' . $value . '</tr>';
        }
        $result .= '</table>';
        return $result;
    }
}
