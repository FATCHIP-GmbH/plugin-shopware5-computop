<?php

namespace Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;

/**
 * Class Service
 *
 * @package Shopware\FatchipCTPayment\Subscribers
 */
class Logger implements SubscriberInterface
{
    /*  @var $logger \Shopware\Components\Logger */
    private $logger;

    protected $plugin;

    protected $config;

    /**
     * Returns the subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend' =>
                'onPostDispatchFatchipCT',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' =>
                'onPostDispatchSecureFatchipCT',
        ];
    }

    public function __construct(){
        $rfh = new \Monolog\Handler\RotatingFileHandler('/srv/http/sw504-computop/logs/FatchipCTPayment_production.log', 14);
        $this->logger = new \Shopware\Components\Logger('FatchipCTPayment');
        $this->logger->pushHandler($rfh);

        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();
    }

    public function isFatchipCTController($controllerName)
    {
        // strpos returns false or int for position
        return is_int(strpos($controllerName, 'FatchipCT'));
    }

    // Todo check here for any exceptions and log them with stack trace??
    // in addition log json response of our Ajax Controllers
    public function onPostDispatchFatchipCT(\Enlight_Controller_ActionEventArgs $args)
    {
        $request = $args->getRequest();
        if ($this->isFatchipCTController($request->getControllerName()) && $this->config['debuglog'] === 'active'){
            $this->logger->debug("postDispatch:" . $request->getControllerName() . " " . $request->getActionName() . ":");
            $this->logger->debug("RequestParams:", $request->getParams());
        }
    }

    // should only be triggered when no exceptions occured
    public function onPostDispatchSecureFatchipCT(\Enlight_Controller_ActionEventArgs $args)
    {
        $subject = $args->getSubject();
        $request = $args->getRequest();

        $test =  $this->config['logLevel'];
        if ($this->isFatchipCTController($request->getControllerName()) && $this->config['debuglog'] === 'active'){

            $this->logger->debug("postDispatchSecure:" . $request->getControllerName() . " " . $request->getActionName() . ":");
            $this->logger->debug("RequestParams:", $request->getParams());

            if ($subject->View()->hasTemplate()){
                $this->logger->debug("Template Name:", $subject->View()->Template()->template_resource);
                $this->logger->debug("Template Vars:", $subject->View()->Engine()->smarty->tpl_vars);
            }
        }
    }
}
