<?php

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
 * PHP version 5.6, 7 , 7.1
 *
 * @category  Payment
 * @package   Computop_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 Computop
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.computop.com
 */

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
        $this->addAttributes('fatchipct', 's_order_attributes', CTPaymentAttributes::orderAttributes);
        $this->addAttributes('fatchipct', 's_order_details_attributes', CTPaymentAttributes::orderDetailsAttributes);
        $this->addAttributes('fatchipct', 's_user_attributes', CTPaymentAttributes::userAttributes);
        // extend address tables depending on sw version
        if (version_compare(\Shopware::VERSION, '5.2.0', '>=')) {
            $this->addAttributes('fatchipct', 's_user_addresses_attributes', CTPaymentAttributes::userAddressAttributes);
        } else {
            $this->addAttributes('fatchipct', 's_user_billingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
            $this->addAttributes('fatchipct', 's_user_shippingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
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
            } catch (\Exception $e) {
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
        if (version_compare(\Shopware::VERSION, '5.2', '>=')) {
            foreach ($attributes as $name => $attribute) {
                try {
                    if (isset($attribute['additionalInfo'])) {
                        $service = $this->plugin->get('shopware_attribute.crud_service');
                        $service->update($table, $prefix . '_' . $name, $attribute['type'], [
                            'label' => $attribute['additionalInfo']['label'],
                            'displayInBackend' => $attribute['additionalInfo']['displayInBackend']
                        ]);
                    }
                } catch (\Exception $e) {
                }
            }
        }
    }
}
