<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Bootstrap
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Fatchip\CTPayment\CTPaymentAttributes;

/**
 * Class Attributes.
 *
 * Adds custom attributes to shopware models.
 */
class Attributes
{

    /**
     * FatchipCTpayment Plugin Bootstrap Class
     * @var \Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    private $plugin;

    /**
     * Attributes constructor.
     */
    public function __construct()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
    }

    /**
     * Extends shopware models with custom attributes.
     *
     * Adds attributes to shopware models by calling addAttributes() helper method.
     *
     * @see addAttributes()
     *
     * @return void
     */
    public function createAttributes()
    {
        $this->addAttributes('fatchipct', 's_order_attributes', CTPaymentAttributes::orderAttributes);
        $this->addAttributes('fatchipct', 's_order_details_attributes', CTPaymentAttributes::orderDetailsAttributes);
        $this->addAttributes('fatchipct', 's_user_attributes', CTPaymentAttributes::userAttributes);
        if (version_compare(\Shopware::VERSION, '5.2.0', '>=')) {
            $this->addAttributes('fatchipct', 's_user_addresses_attributes', CTPaymentAttributes::userAddressAttributes);
        } else {
            $this->addAttributes('fatchipct', 's_user_billingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
            $this->addAttributes('fatchipct', 's_user_shippingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
        }
    }

    /**
     * extends shopware models with custom attributes .
     *
     * Adds attributes to shopware models by calling addAttribute().
     * Regenerates the shopware model
     * Also sets backend visibility attributes for SW >= 5.2
     *
     * @see \Shopware\Components\Model\ModelManager::addAttribute()
     * @see \Shopware\Components\Model\ModelManager::generateAttributeModels()
     *
     * @param string $prefix prefix for attribute db columns
     * @param string $table database table
     * @param array $attributes {
     *
     *      @type string type   attribute type. Default ''. Accepts 'VARCHAR(integer)', 'float', 'DATE'.
     *      @type array additionalInfo {
     *              @type string label              backend label for the attribute. Default ''. Accepts String.
     *              @type string helpText           backend helptext for the attribute. Default ''. Accepts String.
     *              @type string displayInBackend   backend visibility. Default true. Accepts true|false.
     *
     *          }
     * }
     *
     * @return void
     */
    private function addAttributes($prefix, $table, $attributes)
    {
        foreach ($attributes as $name => $attribute) {
            try {
                $this->plugin->get('models')->addAttribute($table, $prefix, $name, $attribute['type']);
            } catch (\Exception $e) {
                // do nothing
            }
        }

        $this->plugin->get('models')->generateAttributeModels(
            [
                $table
            ]
        );

        if (version_compare(\Shopware::VERSION, '5.2', '>=')) {
            $this->setAttributeVisibilityInBackend($prefix, $table, $attributes);
        }
    }

    /**
     * sets backend visibility for custom attributes.
     *
     * Adds attributes to shopware models by calling addAttribute().
     * Regenerates the shopware model
     * Also sets backend visibility attributes for SW >= 5.2
     *
     * @see \Shopware\Bundle\AttributeBundle\Service\CrudService::update()
     *
     * @param string $prefix prefix for attribute db columns
     * @param string $table database table
     * @param array $attributes {
     *
     *      @type string type   attribute type. Default ''. Accepts 'VARCHAR(integer)', 'float', 'DATE'.
     *      @type array additionalInfo {
     *              @type string label              backend label for the attribute. Default ''. Accepts String.
     *              @type string helpText           backend helptext for the attribute. Default ''. Accepts String.
     *              @type string displayInBackend   backend visibility. Default true. Accepts true|false.
     *
     *          }
     * }
     *
     * @return void
     */
    private function setAttributeVisibilityInBackend($prefix, $table, $attributes)
    {
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
                // do nothing
            }
        }
    }
}
