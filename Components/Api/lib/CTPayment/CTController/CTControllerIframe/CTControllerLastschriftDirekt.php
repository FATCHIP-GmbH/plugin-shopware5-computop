<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 15.12.17
 * Time: 17:36
 */

namespace Fatchip\CTPayment\CTController\CTControllerIframe;

use Fatchip\CTPayment\CTController\CTControllerIframe;
use Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseLastschriftDirekt;

class CTControllerLastschriftDirekt extends CTControllerIframe
{
    public function createResponse($data, $len)
    {
        $this->setData($data);
        $this->setLen($len);
        $plaintext = $this->ctDecrypt($data, $len, $this->getBlowfishPassword());
        $arr = array();
        parse_str($plaintext, $arr);
        $response = new CTResponseLastschriftDirekt($arr);
        $this->setResponse($response);
        return $response;
    }
}
