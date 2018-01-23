<?php

namespace Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\FatchipCTPayment\Util;

/**
 * Class Service
 *
 * @package Shopware\FatchipCTPayment\Subscribers
 */
class Utils implements SubscriberInterface
{
    /**
     * Returns the subscribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_InitResource_FatchipCTPaymentUtils' =>
                'onInitUtils',
        ];
    }

    /**
     * @return Util
     */
    public function onInitUtils()
    {
        return new Util();
    }
}
