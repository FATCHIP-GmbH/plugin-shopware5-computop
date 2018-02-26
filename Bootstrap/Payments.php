<?php

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Fatchip\CTPayment\CTPaymentService;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Models\Country\Country;

class Payments
{
    /***
     *  Creates the settings page for this plugin.
     */

    private $plugin;

    public function __construct()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
    }

    /**
     * create payment methods
     */
    public function createPayments()
    {
        /** @var CTPaymentService $service */
        $service = new CTPaymentService(null);
        $paymentMethods = $service->getPaymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            if ($this->plugin->Payments()->findOneBy(array('name' => $paymentMethod['name']))) {
                continue;
            }

            $payment = [
                'name' => $paymentMethod['name'],
                'description' => $paymentMethod['description'],
                'action' => $paymentMethod['action'],
                'active' => 0,
                'template' => $paymentMethod['template'],
                'additionalDescription' => $paymentMethod['additionalDescription'],
            ];

            $paymentObject = $this->plugin->createPayment($payment);

            if (!empty($paymentMethod['countries'])) {
                $this->restrictPaymentShippingCountries($paymentObject, $paymentMethod['countries']);
            }
        }
    }

    private function restrictPaymentShippingCountries($paymentObject, $countries)
    {
        $countryCollection = new ArrayCollection();
        foreach ($countries as $countryIso) {
            $country =
                Shopware()->Models()->getRepository(Country::class)->findOneBy(['iso' => $countryIso]);
            if ($country !== null) {
                $countryCollection->add($country);
            }
        }
        $paymentObject->setCountries($countryCollection);
    }


}
