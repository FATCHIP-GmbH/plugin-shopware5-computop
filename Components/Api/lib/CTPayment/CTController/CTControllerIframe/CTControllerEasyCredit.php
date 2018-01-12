<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 15.12.17
 * Time: 12:31
 */

namespace Fatchip\CTPayment\CTController\CTControllerIframe;

use Fatchip\CTPayment\CTController\CTControllerIframe;
use Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseEasyCredit;

class CTControllerEasyCredit extends CTControllerIframe
{
    public function createResponse($data, $len)
    {
        $this->setData($data);
        $this->setLen($len);
        $plaintext = $this->ctDecrypt($data, $len, $this->getBlowfishPassword());
        $arr = array();
        parse_str($plaintext, $arr);
        $response = new CTResponseEasyCredit($arr);
        $this->setResponse($response);
        return $response;
    }
}
