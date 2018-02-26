<?php

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Doctrine\ORM\Tools\SchemaTool;

class Models
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
     * create tables
     */
    public function createModels()
    {
        $em = $this->plugin->get('models');
        $schemaTool = new SchemaTool($em);
        // ToDo foreach all folders in Models Folder

        try {
            $schemaTool->createSchema(
                [
                    $em->getClassMetadata('Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers'),
                ]
            );
        } catch (\Exception $e) {
            // ignore
        }

        try {
            $schemaTool->createSchema(
                [
                    $em->getClassMetadata('Shopware\CustomModels\FatchipCTApilog\FatchipCTApilog'),
                ]
            );

        } catch (\Exception $e) {
            // ignore
        }
    }
}
