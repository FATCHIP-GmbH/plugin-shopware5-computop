<?php

/**
 * The Computop Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The Computop Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Computop Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7 , 7.1
 *
 * @category  Payment
 * @package   Computop_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 Computop
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.computop.com
 */

namespace Shopware\CustomModels\FatchipCTApilog;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="s_plugin_fatchip_computop_api_log")
 * @ORM\HasLifecycleCallbacks
 */
class FatchipCTApilog extends ModelEntity
{

  /**
   * @var integer $id
   *
   * @ORM\Column(name="id", type="integer", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
    private $id;

  /**
   * will be used to save the type of the request to computop
   * so we can easily filter
   * "CreditCard" "EasyCredit"
   *
   * @ORM\Column(name="request", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
   */
    private $request;

  /**
   *  response status field "OK" or "Error"
   * @ORM\Column(name="response", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
   */
    private $response;

   /**
   * @ORM\Column(name="creation_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
   */
    private $creationDate;

    /**
     * @ORM\Column(name="payment_name", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $paymentName;


    /**
   * @ORM\Column(name="request_details", type="array", precision=0, scale=0, nullable=true, unique=false)
   */
    private $requestDetails;

  /**
   * @ORM\Column(name="response_details", type="array", precision=0, scale=0, nullable=true, unique=false)
   */
    private $responseDetails;

    /**
     * @var string $transId
     * @ORM\Column(name="trans_id", length=255, type="string", nullable=true)
     */
    private $transId;

    /**
     * @var string $PayId
     * @ORM\Column(name="pay_id", length=255, type="string", nullable=true)
     */
    private $payId;

    /**
     * @var string $x
     * @ORM\Column(name="x_id", length=255, type="string", nullable=true)
     */
    private $xId;


    /**
     * automatically insert timestamp
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->creationDate = new \DateTime();
    }


    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
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
    public function getPaymentName()
    {
        return $this->paymentName;
    }

    /**
     * @param mixed $response
     */
    public function setPaymentName($paymentName)
    {
        $this->paymentName = $paymentName;
    }


    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param mixed $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return mixed
     */
    public function getRequestDetails()
    {
        return $this->requestDetails;
    }

    /**
     * @param mixed $requestDetails
     */
    public function setRequestDetails($requestDetails)
    {
        $this->requestDetails = $requestDetails;
    }

    /**
     * @return mixed
     */
    public function getResponseDetails()
    {
        return $this->responseDetails;
    }

    /**
     * @param mixed $responseDetails
     */
    public function setResponseDetails($responseDetails)
    {
        $this->responseDetails = $responseDetails;
    }

    /**
     * @return string
     */
    public function getTransId()
    {
        return $this->transId;
    }

    /**
     * @param string $transId
     */
    public function setTransId($transId)
    {
        $this->transId = $transId;
    }

    /**
     * @return string
     */
    public function getPayId()
    {
        return $this->payId;
    }

    /**
     * @param string $payId
     */
    public function setPayId($payId)
    {
        $this->payId = $payId;
    }

    /**
     * @return string
     */
    public function getXId()
    {
        return $this->xId;
    }

    /**
     * @param string $xId
     */
    public function setXId($xId)
    {
        $this->xId = $xId;
    }
}

