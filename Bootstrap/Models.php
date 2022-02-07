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
                    $em->getClassMetadata('Shopware\CustomModels\FatchipFCSIdeal\FatchipFCSIdealIssuers'),
                ]
            );
        } catch (Exception $e) {
            // ignore
        }

        try {
            $schemaTool->createSchema(
                [
                    $em->getClassMetadata('Shopware\CustomModels\FatchipFCSApilog\FatchipFCSApilog'),
                ]
            );
        } catch (Exception $e) {
            // ignore
        }
    }
}
