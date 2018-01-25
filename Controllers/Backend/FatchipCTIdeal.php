<?php

use Fatchip\CTPayment\CTPaymentService;
use Fatchip\CTPayment\CTIdealIssuerService;


class Shopware_Controllers_Backend_FatchipCTIdeal extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    private $plugin;

    private $config;

    /**
     * @var CTPaymentService
     */
    private $paymentService;

    /** @var Util $utils * */
    protected $utils;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        $this->utils = Shopware()->Container()->get('FatchipCTPaymentUtils');
        parent::init();
    }

    public function getIdealIssuerListAction()
    {
        $service = new CTIdealIssuerService($this->config);
        $issuerList = $service->getIssuerList();

        $count = 0;
        // only fill if empty for now
        // ToDo implement update mechanism
        $test = Shopware()->Models()->getRepository('Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers')->findAll();
        if (empty($test)) {

            try {

                foreach ($issuerList as $issuer) {
                    $issuerModel = new \Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers();
                    $issuerModel->fromArray($issuer);
                    Shopware()->Models()->persist($issuerModel);
                    $count++;
                }
                Shopware()->Models()->flush($issuerModel);
            } catch (Exception $e) {
            }

            if ($count > 0) {
                $this->View()->assign(array('success' => true, 'count' => $count));
            } else {
                $this->View()->assign(array('success' => false, 'error' => $e->getMessage()));
            }
        }
    }

    public function getWhitelistedCSRFActions()
    {
        $csrfActions = array(
            'getIdealIssuerList'
        );

        return $csrfActions;
    }
}
