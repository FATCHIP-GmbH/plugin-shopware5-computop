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

namespace Shopware\Plugins\FatchipFCSPayment\Bootstrap;

use Shopware_Plugins_Frontend_FatchipFCSPayment_Bootstrap;

/**
 * Class Menu.
 *
 * creates the computop menu entry in the shopware backend.
 */
class Menu extends Bootstrap
{
    const LABELPARENTFIND = ['label' => 'Einstellungen'];
    const LABELCOMPUTOPMENU = 'Computop';
    const LABELCOMPUTOPAPILOG = 'API Protokoll';

    /**
     * Create menu items to access configuration, logs and support page
     *
     * @see Shopware_Components_Plugin_Bootstrap::createMenuItem()
     *
     * @return void
     */
    public function createMenu()
    {
        $ret = $this->plugin->Menu()->findOneBy(['label' => self::LABELCOMPUTOPAPILOG]);
        if (!$ret) {
            $item = $this->plugin->createMenuItem(
                [
                    'label' => self::LABELCOMPUTOPMENU,
                    'class' => 'computop-icon',
                    'active' => 1,
                    'parent' => $this->plugin->Menu()->findOneBy(['label' => self::LABELPARENTFIND]),
                ]
            );
            $this->plugin->createMenuItem(
                [
                    'label' => self::LABELCOMPUTOPAPILOG,
                    'class' => 'computop-icon',
                    'active' => 1,
                    'action' => 'index',
                    'controller' => 'FatchipCTApilog',
                    'parent' => $item,
                ]
            );
        }
    }
}
