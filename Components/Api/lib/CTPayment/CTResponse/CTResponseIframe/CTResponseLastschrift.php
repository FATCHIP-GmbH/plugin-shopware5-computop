<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 04.12.17
 * Time: 11:27
 */

namespace Fatchip\CTPayment\CTResponse\CTResponseIframe;

use Fatchip\CTPayment\CTResponse\CTResponseIframe;

abstract class CTResponseLastschrift extends CTResponseIframe
{
    protected $Type;

    protected $IBAN;

    protected $BIC;

    protected $AccOwner;

    protected $Mandateid;

    protected $Dtofsgntr;

    protected $Mdtseqtype;

    /**
     * @param mixed $AccOwner
     */
    public function setAccOwner($AccOwner)
    {
        $this->AccOwner = $AccOwner;
    }

    /**
     * @return mixed
     */
    public function getAccOwner()
    {
        return $this->AccOwner;
    }

    /**
     * @param mixed $BIC
     */
    public function setBIC($BIC)
    {
        $this->BIC = $BIC;
    }

    /**
     * @return mixed
     */
    public function getBIC()
    {
        return $this->BIC;
    }

    /**
     * @param mixed $Dtofsgntr
     */
    public function setDtofsgntr($Dtofsgntr)
    {
        $this->Dtofsgntr = $Dtofsgntr;
    }

    /**
     * @return mixed
     */
    public function getDtofsgntr()
    {
        return $this->Dtofsgntr;
    }

    /**
     * @param mixed $IBAN
     */
    public function setIBAN($IBAN)
    {
        $this->IBAN = $IBAN;
    }

    /**
     * @return mixed
     */
    public function getIBAN()
    {
        return $this->IBAN;
    }

    /**
     * @param mixed $Mandateid
     */
    public function setMandateid($Mandateid)
    {
        $this->Mandateid = $Mandateid;
    }

    /**
     * @return mixed
     */
    public function getMandateid()
    {
        return $this->Mandateid;
    }

    /**
     * @param mixed $Mdtseqtype
     */
    public function setMdtseqtype($Mdtseqtype)
    {
        $this->Mdtseqtype = $Mdtseqtype;
    }

    /**
     * @return mixed
     */
    public function getMdtseqtype()
    {
        return $this->Mdtseqtype;
    }

    /**
     * @param mixed $Type
     */
    public function setType($Type)
    {
        $this->Type = $Type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->Type;
    }



    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }
}
