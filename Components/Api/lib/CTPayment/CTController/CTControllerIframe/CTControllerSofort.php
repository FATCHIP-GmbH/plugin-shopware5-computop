<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 14.12.17
 * Time: 14:46
 */

namespace Fatchip\CTPayment\CTController\CTControllerIframe;

use Fatchip\CTPayment\CTController\CTControllerIframe;
use Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseSofort;

class CTControllerSofort extends CTControllerIframe
{
    public function createResponse($data, $len)
    {
        $this->setData($data);
        $this->setLen($len);
        $plaintext = $this->ctDecrypt($data, $len, $this->getBlowfishPassword());
        $arr = array();
        parse_str($plaintext, $arr);
        $response = new CTResponseSofort($arr);
        $this->setResponse($response);
        return $response;
    }
}
