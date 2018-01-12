<?php

namespace Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Fatchip\CTPayment\CTPaymentService;

/**
 * Class Service
 *
 * @package Shopware\FatchipCTPayment\Subscribers
 */
class Service implements SubscriberInterface
{
    /**
     * Returns the subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_InitResource_FatchipCTPaymentApiClient' =>
                'onInitApiClient',
        ];
    }

    /**
     * @return CTPaymentService
     */
    public function onInitApiClient()
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . '../Components/Api/vendor/autoload.php';
        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $config = $plugin->Config()->toArray();
        return new CTPaymentService($config['blowfishPassword']);

    }
}
