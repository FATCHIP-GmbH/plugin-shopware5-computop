<?php
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
 * PHP version 5.6, 7.0 , 7.1
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

use Shopware_Plugins_Frontend_FatchipFCSPayment_Bootstrap;

/**
 * Class Menu.
 *
 * creates the firstcash menu entry in the shopware backend.
 */
class Menu extends Bootstrap
{
    const LABELPARENTFIND = ['label' => 'Einstellungen'];
    const LABELFIRSTCASHMENU = 'First Cash';
    const LABELFIRSTCASHAPILOG = 'API Protokoll';

    /**
     * Create menu items to access configuration, logs and support page
     *
     * @see Shopware_Components_Plugin_Bootstrap::createMenuItem()
     *
     * @return void
     */
    public function createMenu()
    {
        $ret = $this->plugin->Menu()->findOneBy(['label' => self::LABELFIRSTCASHAPILOG]);
        if (!$ret) {
            $item = $this->plugin->createMenuItem(
                [
                    'label' => self::LABELFIRSTCASHMENU,
                    'class' => 'firstcash-icon',
                    'active' => 1,
                    'parent' => $this->plugin->Menu()->findOneBy(['label' => self::LABELPARENTFIND]),
                ]
            );
            $this->plugin->createMenuItem(
                [
                    'label' => self::LABELFIRSTCASHAPILOG,
                    'class' => 'firstcash-icon',
                    'active' => 1,
                    'action' => 'index',
                    'controller' => 'FatchipFCSApilog',
                    'parent' => $item,
                ]
            );
        }
    }
}
