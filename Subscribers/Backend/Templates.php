<?php

/**
 * The First Cash Solution Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The First Cash Solution Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with First Cash Solution Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */

namespace Shopware\Plugins\FatchipFCSPayment\Subscribers\Backend;

use Enlight\Event\SubscriberInterface;


class Templates implements SubscriberInterface
{
    /**
     * Returns the subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'onPostDispatchBackendIndex',
            'Enlight_Controller_Action_PostDispatch_Backend_RiskManagement' => 'onBackendRiskManagementPostDispatch',
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'fatchipFCSExtendController_Backend_Order'
        ];
    }

    /**
     * Extends Backend header with CSS to display CT-Icon in Menu
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendIndex(\Enlight_Controller_ActionEventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Index $subject */
        $subject = $args->get('subject');
        $request = $subject->Request();
        $response = $subject->Response();
        $view = $subject->View();

        $view->addTemplateDir(__DIR__ . '/../../Views');

        if ( ! $request->isDispatched()
            || $response->isException()
            || $request->getModuleName() !== 'backend'
            || ! $view->hasTemplate()
        ) {
            return;
        }

        $view->extendsTemplate('responsive/backend/index/firstcashsolution.tpl');
    }

    /**
     * Adds Javascript to risk management backend
     *
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onBackendRiskManagementPostDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->extendsTemplate('backend/fcfcs_risk_management/controller/main.js');
        $view->extendsTemplate('backend/fcfcs_risk_management/controller/risk_management.js');
        $view->extendsTemplate('backend/fcfcs_risk_management/store/risks.js');
        $view->extendsTemplate('backend/fcfcs_risk_management/store/trafficLights.js');
        $view->extendsTemplate('backend/fcfcs_risk_management/view/risk_management/container.js');
    }

    /**
     * Adds Javascript to order backend
     *
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function fatchipFCSExtendController_Backend_Order(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->extendsTemplate('backend/fcfcs_order/controller/detail.js');
        $view->extendsTemplate('backend/fcfcs_order/model/position.js');
        $view->extendsTemplate('backend/fcfcs_order/view/detail/overview.js');
        $view->extendsTemplate('backend/fcfcs_order/view/detail/position.js');
    }
}
