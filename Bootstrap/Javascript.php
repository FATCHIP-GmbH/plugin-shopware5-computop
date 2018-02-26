<?php

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Doctrine\Common\Collections\ArrayCollection;

class Javascript
{
    /***
     *  Creates the settings page for this plugin.
     */

    private $plugin;

    public function __construct()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
    }

    public function registerJs()
    {
        $this->plugin->subscribeEvent(
            'Theme_Compiler_Collect_Plugin_Javascript',
            'addJsFiles'
        );
    }

    public function addJsFiles(Enlight_Event_EventArgs $args)
    {
        $jsFiles = [
            $this->Path() . '../Views/responsive/frontend/_resources/javascript/fatchipCTAmazon.js',
            $this->Path() . '../Views/responsive/frontend/_resources/javascript/fatchipCTPaypalExpress.js',
        ];
        return new ArrayCollection($jsFiles);
    }
}
