<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTPaymentMethodIframe;

class PostFinance extends CTPaymentMethodIframe
{
    /**
     * Name des Kontoinhabers
     *
     * @var string
     */
    protected $AccOwner;

    public function __construct(
      $config,
      $order,
      $URLSuccess,
      $URLFailure,
      $URLNotify
    ) {
        parent::__construct($config, $order);
        $this->setURLSuccess($URLSuccess);
        $this->setURLFailure($URLFailure);
        $this->setURLNotify($URLNotify);
        $this->setAccOwner($order->getBillingAddress()->getFirstName . ' ' . $order->getBillingAddress()->getLastName());
        $this->setMandatoryFields(array('MerchantID', 'TransID', 'Amount', 'Currency', 'MAC', 'OrderDesc',
          'URLSuccess', 'URLFailure', 'URLNotify', 'AccOwner' ));
    }

    /**
     * @param string $AccOwner
     */
    public function setAccOwner($AccOwner)
    {
        $this->AccOwner = $AccOwner;
    }

    /**
     * @return string
     */
    public function getAccOwner()
    {
        return $this->AccOwner;
    }


    public function getCTPaymentURL()
    {
        return 'https://www.computop-paygate.com/postfinance.aspx';
    }

    public function getCTRefundURL()
    {
        return 'https://www.computop-paygate.com/credit.aspx';
    }

    public function getSettingsDefinitions()
    {
        return null;
    }
}
