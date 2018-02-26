<?php

namespace Shopware\Plugins\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container;

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
     * return array with all subsribed events
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
