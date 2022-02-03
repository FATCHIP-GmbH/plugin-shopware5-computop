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
 * @link       https://www.firstcash.com
 */

namespace Shopware\Plugins\FatchipFCSPayment\Bootstrap;

use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use Shopware\Components\Model\ModelManager;
use Shopware_Plugins_Frontend_FatchipFCSPayment_Bootstrap;

/**
 * Class Models.
 *
 * creates our custom db models.
 */
class Models extends Bootstrap
{
    /**
     * Create db tables / models
     *
     * @see SchemaTool::createSchema()
     * @see ModelManager::getClassMetadata()
     *
     * @return void
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
        } catch (Exception $e) {
            // ignore
        }

        try {
            $schemaTool->createSchema(
                [
                    $em->getClassMetadata('Shopware\CustomModels\FatchipCTApilog\FatchipCTApilog'),
                ]
            );
        } catch (Exception $e) {
            // ignore
        }
    }
}
