<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The First Cash Solution Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The First Cash Solution Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with First Cash Solution Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Bootstrap
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */

namespace Shopware\Plugins\FatchipFCSPayment\Bootstrap;

use Exception;
use Fatchip\FCSPayment\CTPaymentAttributes;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Plugins\FatchipFCSPayment\Util;

/**
 * Class Attributes.
 *
 * Adds custom attributes to shopware models.
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Bootstrap
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */
class Attributes extends Bootstrap
{
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
        $this->addAttributes('fatchipfcs', 's_order_attributes', CTPaymentAttributes::orderAttributes);
        $this->addAttributes('fatchipfcs', 's_order_details_attributes', CTPaymentAttributes::orderDetailsAttributes);
        $this->addAttributes('fatchipfcs', 's_user_attributes', CTPaymentAttributes::userAttributes);
        if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $this->addAttributes('fatchipfcs', 's_user_addresses_attributes', CTPaymentAttributes::userAddressAttributes);
        } else {
            $this->addAttributes('fatchipfcs', 's_user_billingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
            $this->addAttributes('fatchipfcs', 's_user_shippingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
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
    private function addAttributes($prefix, $table, $attributes)
    {
        foreach ($attributes as $name => $attribute) {
            try {
                if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
                    /** @var CrudService $crudService */
                    $crudService = Shopware()->Container()->get('shopware_attribute.crud_service');
                    $crudService->update($table, $prefix . '_'. $name, $attribute['type']);
                } else {
                    $this->plugin->get('models')->addAttribute($table, $prefix, $name, $attribute['type']);
                }
            } catch (Exception $e) {
                // do nothing
            }
        }

        $this->plugin->get('models')->generateAttributeModels(
            [
                $table
            ]
        );

        if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
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
                    $crudService = $this->plugin->get('shopware_attribute.crud_service');
                    $crudService->update($table, $prefix . '_' . $name, $attribute['type'], [
                        'label' => $attribute['additionalInfo']['label'],
                        'displayInBackend' => $attribute['additionalInfo']['displayInBackend']
                    ]);
                }
            } catch (Exception $e) {
                // do nothing
            }
        }
    }
}
