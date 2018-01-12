<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 14.12.17
 * Time: 14:00
 */

namespace Fatchip\CTPayment\CTController;

use Fatchip\CTPayment\Blowfish;

abstract class CTController extends Blowfish
{
    private $data;

    private $len;

    /**     *
     * @var CTResponse
     */
    private $response;

    public function __construct($blowfishpassword)
    {
        $this->setBlowfishPassword($blowfishpassword);
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $len
     */
    public function setLen($len)
    {
        $this->len = $len;
    }

    /**
     * @return mixed
     */
    public function getLen()
    {
        return $this->len;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }



    /**
     * Create HTML with parameters in a NVP array
     *
     * Split the elements in the passed array $arText by the split-string $sSplit. Return the result as html table rows.
     * If $sArg is passed, return only the matching row.
     *
     * @param string[] $arText
     * @param string $sSplit
     * @param string $sArg
     * @return string
     */
    private function ctSplit($arText, $sSplit, $sArg = "")
    {
        $b = "";
        $i = 0;
        $info = '';

        while ($i < count($arText)) {
            $b = explode($sSplit, $arText [$i++]);

            if ($b[0] == $sArg) {                // check for $sArg
                $info = $b[1];
                $b = 0;
                break;
            } else {
                $info .= '<tr><td align=right>' . $b[0] . '</td><td>"' . $b[1] . '"</td></tr>';
            }
        }

        if ((strlen($sArg) > 0) & ($b != 0)) {   // $sArg not found
            $info = "";
        }

        return $info;
    }

    public function render()
    {
        $plaintext = $this->ctDecrypt($this->getData(), $this->getLen(), '9q!JX2c(]D3og7?G');
        $a = explode('&', $plaintext);
        $info = $this->ctSplit($a, '=');
        print_r('<table>' . $info . '</table>');
    }

    abstract public function createResponse($data, $len);
}
