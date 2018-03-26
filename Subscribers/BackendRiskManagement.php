<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container;
/**
 * Class BackendRiskManagement
 *
 * @package Shopware\Plugins\FatchipCTPayment\Subscribers
 */
class BackendRiskManagement implements SubscriberInterface
{

    /**
     * di container
     *
     * @var Container
     */
    private $container;

    /**
     * inject di container
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * return array with all subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            //risk management:Backend options
            'Enlight_Controller_Action_PostDispatch_Backend_RiskManagement' => 'onBackendRiskManagementPostDispatch'
        );
    }

    /**
     * Adds Javascript to Riskmanagement backend
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onBackendRiskManagementPostDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->extendsTemplate('backend/fcct_risk_management/controller/main.js');
        $view->extendsTemplate('backend/fcct_risk_management/controller/risk_management.js');
        $view->extendsTemplate('backend/fcct_risk_management/store/risks.js');
        $view->extendsTemplate('backend/fcct_risk_management/store/trafficLights.js');
        $view->extendsTemplate('backend/fcct_risk_management/view/risk_management/container.js');
    }
}
