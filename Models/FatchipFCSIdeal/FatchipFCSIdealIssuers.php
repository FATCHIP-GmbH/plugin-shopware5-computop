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
 * @subpackage Models_FatchipFCSIdeal
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */

namespace Shopware\CustomModels\FatchipFCSIdeal;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FatchipFCSIdealIssuers
 *
 * @ORM\Entity()
 * @ORM\Table(name="s_plugin_fatchip_firstcash_ideal_issuers")
 */
class FatchipFCSIdealIssuers extends ModelEntity
{

  /**
   * Id
   * @var integer $id
   *
   * @ORM\Column(name="id", type="integer", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
    private $id;

  /**
   * IssuerId
   * @ORM\Column(name="issuer_id", type="string", length=11, nullable=false, unique=false)
   */
    private $issuerId;

    /**
     * Name of the Issuer
     * @ORM\Column(name="name", type="string", length=128, nullable=false, unique=false)
     */
    private $name;

    /**
     * Country of the Issuer
     * @ORM\Column(name="land", type="string", length=128, nullable=false, unique=false)
     */
    private $land;

    /**
     * @ignore <description>
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ignore <description>
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @ignore <description>
     * @return mixed
     */
    public function getIssuerId()
    {
        return $this->issuerId;
    }

    /**
     * @ignore <description>
     * @param mixed $issuerId
     */
    public function setIssuerId($issuerId)
    {
        $this->issuerId = $issuerId;
    }

    /**
     * @ignore <description>
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @ignore <description>
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @ignore <description>
     * @return mixed
     */
    public function getLand()
    {
        return $this->land;
    }

    /**
     * @ignore <description>
     * @param mixed $land
     */
    public function setLand($land)
    {
        $this->land = $land;
    }
}
