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

class Menu
{
    /***
     *  Creates the settings page for this plugin.
     */

    private $plugin;

    const labelParentFind = ['label' => 'Einstellungen'];
    const labelComputopMenu = 'Computop';
    const labelComputopApiLog = 'Apilog';

    public function __construct()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
    }

    /**
     * Create menu items to access configuration, logs and support page
     */
    public function createMenu()
    {
        $item = $this->plugin->createMenuItem(
            [
                'label' => self::labelComputopMenu,
                'class' => 'computop-icon',
                'active' => 1,
                'parent' => $this->plugin->Menu()->findOneBy(self::labelParentFind),
            ]
        );
        $this->plugin->createMenuItem(
            [
                'label' => self::labelComputopApiLog,
                'class' => 'computop-icon',
                'active' => 1,
                'action' => 'index',
                'controller' => 'FatchipCTApilog',
                'parent' => $item,
            ]
        );
    }
}
