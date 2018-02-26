<?php

namespace Shopware\Plugins\FatchipCTPayment\Subscribers;

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
           'Enlight_Controller_Action_PostDispatch_Backend_Index' =>
             'onPostDispatchBackendIndex',
        ];
    }

    public function __construct(){

        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $this->config = $this->plugin->Config()->toArray();

        // ToDO use terniary operator here
        // Shopware()->Application() is deprecated
        $logPath = Shopware()->DocPath();
        if (version_compare(\Shopware::VERSION, '5.1', '>=')){
            $logFile = $logPath . 'var/log/FatchipCTPayment_production.log';
        } else {
            $logFile = $logPath . 'logs/FatchipCTPayment_production.log';
        }
        $rfh = new \Monolog\Handler\RotatingFileHandler($logFile, 14);
        $this->logger = new \Shopware\Components\Logger('FatchipCTPayment');
        $this->logger->pushHandler($rfh);
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

        if ($this->isFatchipCTController($request->getControllerName()) && $this->config['debuglog'] === 'active'){

            $this->logger->debug("postDispatchSecure:" . $request->getControllerName() . " " . $request->getActionName() . ":");
            $this->logger->debug("RequestParams:", $request->getParams());

            if ($subject->View()->hasTemplate()){
                $this->logger->debug("Template Name:", [$subject->View()->Template()->template_resource]);
                $this->logger->debug("Template Vars:", $subject->View()->Engine()->smarty->tpl_vars);
            }
        }
    }
    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendIndex(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Index $subject */
        $subject = $args->get('subject');
        $request = $subject->Request();
        $response = $subject->Response();
        $view = $subject->View();

        $view->addTemplateDir(__DIR__ . '/../Views');

        if (!$request->isDispatched() ||
          $response->isException() ||
          $request->getModuleName() != 'backend' ||
          !$view->hasTemplate()) {
            return;
        }
        $view->extendsTemplate('responsive/backend/index/computop.tpl');
    }
}
