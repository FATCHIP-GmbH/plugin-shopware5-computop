<?php

namespace Fatchip\CTPayment\CTOrder;
use Fatchip\CTPayment\CTAddress\CTAddress;

/**
 * Class CTOrder
 * @package Fatchip\CTOrder
 */
class CTOrder
{
    protected $Amount;
    protected $Currency;
    protected $orderDesc;
    protected $PayId;
    /**
     * @var CTAddress
     */
    protected $billingAddress;
    /**
     * @var CTAddress
     */
    protected $shippingAddress;

    public function __construct()
    {
    }


    /**
     * @param mixed $Amount
     */
    public function setAmount($Amount)
    {
        $this->Amount = $Amount;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->Amount;
    }

    /**
     * @param mixed $Currency
     */
    public function setCurrency($Currency)
    {
        $this->Currency = $Currency;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->Currency;
    }

    /**
     * @param mixed $PayId
     */
    public function setPayId($PayId)
    {
        $this->PayId = $PayId;
    }

    /**
     * @return mixed
     */
    public function getPayId()
    {
        return $this->PayId;
    }

    /**
     * @param mixed $orderDescription
     */
    public function setOrderDesc($orderDescription)
    {
        $this->orderDesc = $orderDescription;
    }

    /**
     * @return mixed
     */
    public function getOrderDesc()
    {
        return $this->orderDesc;
    }

    /**
     * @param \Fatchip\CTPayment\CTAddress\CTAddress $billingAddress
     */
    public function setBillingAddress($billingAddress) {
        $this->billingAddress = $billingAddress;
    }

    /**
     * @return \Fatchip\CTPayment\CTAddress\CTAddress
     */
    public function getBillingAddress() {
        return $this->billingAddress;
    }

    /**
     * @param \Fatchip\CTPayment\CTAddress\CTAddress $shippingAddress
     */
    public function setShippingAddress($shippingAddress) {
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @return \Fatchip\CTPayment\CTAddress\CTAddress
     */
    public function getShippingAddress() {
        return $this->shippingAddress;
    }

}
