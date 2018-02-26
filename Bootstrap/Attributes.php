<?php

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Fatchip\CTPayment\CTPaymentAttributes;

class Attributes
{
    /***
     *  Creates the settings page for this plugin.
     */

    private $plugin;

    public function __construct()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
    }

    public function createAttributes()
    {
        // extend order model
        $this->addAttributes('fatchipCT', 's_order_attributes', CTPaymentAttributes::orderAttributes);
        $this->addAttributes('fatchipCT', 's_order_details_attributes', CTPaymentAttributes::orderDetailsAttributes);
        $this->addAttributes('fatchipCT', 's_user_attributes', CTPaymentAttributes::userAttributes);
        // extend address tables depending on sw version
        if (version_compare(\Shopware::VERSION, '5.2.0', '>=')) {
            $this->addAttributes('fatchipCT', 's_user_addresses_attributes', CTPaymentAttributes::userAddressAttributes);
        } else {
            $this->addAttributes('fatchipCT', 's_user_billingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
            $this->addAttributes('fatchipCT', 's_user_shippingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
        }
    }

    /**
     * @param string $prefix
     * @param string $table
     * @param array $attributes
     */
    private function addAttributes($prefix, $table, $attributes)
    {
        foreach ($attributes as $name => $attribute) {
            try {
                $this->plugin->get('models')->addAttribute($table, $prefix, $name, $attribute['type']);
            } catch (Exception $e) {
            }
        }

        $this->plugin->get('models')->generateAttributeModels(
            [
                $table
            ]
        );

        $this->setAttributeVisibilityInBackend($prefix, $table, $attributes);
    }

    private function setAttributeVisibilityInBackend($prefix, $table, $attributes)
    {
        if (version_compare(\Shopware::VERSION, '5.2.0', '>=')) {
            foreach ($attributes as $name => $attribute) {
                try {
                    if (isset($attribute['additionalInfo'])) {
                        $updateType = $attribute['type'] == 'DOUBLE' ? 'float' : $attribute['type'];
                        $service = $this->plugin->get('shopware_attribute.crud_service');
                        $service->update($table, $prefix . '_' . $name, $updateType, [
                            'label' => $attribute['additionalInfo']['label'],
                            'displayInBackend' => $attribute['additionalInfo']['displayInBackend']
                        ]);
                    }
                } catch (Exception $e) {
                }
            }
        }
    }
}
