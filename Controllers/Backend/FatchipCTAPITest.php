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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Backend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

use Fatchip\CTPayment\CTPaymentService;
use Fatchip\CTPayment\CTAPITestService;

/**
 * controller for validation API credentials
 */
class Shopware_Controllers_Backend_FatchipCTAPITest extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * FatchipFCSpayment Plugin Bootstrap Class
     * @var \Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    private $plugin;

    /**
     * FatchipFCSPayment Configuration
     * @var array
     */
    private $config;

    /**
     * Payment Service
     * @var CTPaymentService
     */
    private $paymentService;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
        $this->paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');
        parent::init();
    }

    /**
     * assigns error and count of updated items to view
     *
     * @return void
     */
    public function apiTestAction()
    {
        $service = new CTAPITestService($this->config);
        try {
            $success = $service->doAPITest();
        } catch (Exception $e) {
            $success = false;
        }

        if ($success) {
            $this->View()->assign(['success' => true]);
        } else {
            $this->View()->assign(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * prevents CSRF Token errors
     * @return array
     */
    public function getWhitelistedCSRFActions()
    {
        $csrfActions = ['apiTestAction'];

        return $csrfActions;
    }
}
