<?php

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
