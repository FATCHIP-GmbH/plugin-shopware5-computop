<?php

namespace Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Bridge\Plugins;

/**
 * Class Service
 *
 * @package Shopware\FatchipCTPayment\Subscribers
 */
class Logger implements SubscriberInterface
{
    /**
     * Returns the subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        /*
        return [
            'Enlight_Controller_Action_PreDispatch_Frontend' =>
                'onPreDispatchFatchipCT',
            'Enlight_Controller_Action_PostDispatch_Frontend' =>
                'onPostDispatchFatchipCT',
            'Enlight_Controller_Action_PostDispatchSecure' =>
                'onPostDispatchSecureFatchipCT',
        ];
        */

       /* return [
            'Shopware_Controllers_Frontend_FatchipCTPayment::callComputopService::replace' =>
            'onReplaceCTServiceCall'
        ];
       */
        return [
            'Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap::callComputopService::replace' =>
                'onReplaceCTServiceCall'
        ];

    }

    public function isFatchipCTController($controllerName)
    {
        // strpos returns false or int for position
        return is_int(strpos($controllerName, 'FatchipCT'));
    }


    // Log Arguments??
    public function onPreDispatchFatchipCT(\Enlight_Controller_ActionEventArgs $args)
    {
        // Note Request and Response hints are the parent Class!!!
        $class = $args->getSubject();
        /** @var \Enlight_Controller_Request_Request $request */
        $request = $args->getRequest();
        /** @var \Enlight_Controller_Response_Response $request */
        $response = $args->getResponse();
        if ($this->isFatchipCTController($request->getControllerName())){
            // get RequestParams Array and log them for debugging
            $requestParams =  $request->getParams();
            //$responseParams = $response->getParams();
            // check if there is a fatchipCTResponse and log this to Logging Model
            if ($requestParams['fatchipCTResponse']){
            }

        }
    }

    // check here for any exceptions??
    public function onPostDispatchFatchipCT(\Enlight_Controller_ActionEventArgs $args)
    {
        // Note Request and Response hints are the parent Class!!!
        $class = $args->getSubject();
        /** @var \Enlight_Controller_Request_Request $request */
        $request = $args->getRequest();

        /** @var \Enlight_Controller_Response_Response $request */
        $response = $args->getResponse();
        if ($this->isFatchipCTController($request->getControllerName())){
            // get RequestParams and log them
            $params = $request->getParams();
        }
    }

    // should only be triggered when no exceptions occured
    public function onPostDispatchSecureFatchipCT(\Enlight_Controller_ActionEventArgs $args)
    {
        // Note Request and Response hints are the parent Class!!!
        $class = $args->getSubject();
        /** @var \Enlight_Controller_Request_Request $request */
        $request = $args->getRequest();

        /** @var \Enlight_Controller_Response_Response $request */
        $response = $args->getResponse();
        if ($this->isFatchipCTController($request->getControllerName())){
            // get RequestParams and log them
            $params = $request->getParams();
        }
    }

    // should only be triggered when no exceptions occured
    public function onReplaceCTServiceCall(\Enlight_Hook_HookArgs $args)
    {
        // ToDo somehow get the called Computop Action
        // Eventtoken for Amazon, Capture, Refund, etc.
        $class = $args->getSubject();
        $method = $args->getMethod();

        // call original Method
        $args->setReturn($args->getSubject()->executeParent(
            $method,
            $args->getArgs()
        ));
        // ToDo check for Exceptions and Log them??
        $requestParams = $args->requestParams;
        $responseParams = $args->getReturn();

        $log = new \Shopware\CustomModels\FatchipCTApilog\FatchipCTApilog();
        $log->setTransId($requestParams['TransID']);
        // Todo find a solution to get the paymentname from Classname
        // ToDo implement getPaymentNameFromClassName
        $log->setPaymentName('AmazonPay');
        $log->setRequest($requestParams['EventToken']);
        $log->setRequestDetails(json_encode($requestParams));
        $log->setPayId($responseParams['PayID']);
        $log->setXId($responseParams['XID']);
        $log->setResponse($responseParams['Status']);
        $log->setResponseDetails(json_encode($responseParams));
        Shopware()->Models()->persist($log);
        Shopware()->Models()->flush($log);
    }

    /**
     * decorate Service
     */
    public function decorateService()
    {
        $coreService  = Shopware()->Container()->get('shopware_storefront.list_product_service');
        $mtwService = new MtwProductService($coreService);
        Shopware()->Container()->set('shopware_storefront.list_product_service', $mtwService);
    }
}
