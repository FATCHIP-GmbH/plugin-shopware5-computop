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
