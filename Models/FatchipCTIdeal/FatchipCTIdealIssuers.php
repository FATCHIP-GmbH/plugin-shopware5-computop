<?php

namespace Shopware\CustomModels\FatchipCTIdeal;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="s_plugin_fatchip_computop_ideal_issuers")
 */
class FatchipCTIdealIssuers extends ModelEntity
{

  /**
   * @var integer $id
   *
   * @ORM\Column(name="id", type="integer", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
    private $id;

  /**
   * @ORM\Column(name="issuer_id", type="string", length=11, nullable=false, unique=false)
   */
    private $issuerId;

    /**
     * @ORM\Column(name="name", type="string", length=128, nullable=false, unique=false)
     */
    private $name;

    /**
     * @ORM\Column(name="land", type="string", length=128, nullable=false, unique=false)
     */
    private $land;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getIssuerId()
    {
        return $this->issuerId;
    }

    /**
     * @param mixed $issuerId
     */
    public function setIssuerId($issuerId)
    {
        $this->issuerId = $issuerId;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getLand()
    {
        return $this->land;
    }

    /**
     * @param mixed $land
     */
    public function setLand($land)
    {
        $this->land = $land;
    }
}
