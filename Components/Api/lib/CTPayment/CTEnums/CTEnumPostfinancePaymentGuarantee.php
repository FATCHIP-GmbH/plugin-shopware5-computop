<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 04.12.17
 * Time: 12:10
 */

namespace Fatchip\CTPayment\CTEnums;

class CTEnumPostfinancePaymentGuarantee
{
    /**
     * keine Zahlungsgarantie
     */
    const NONE = 'NONE';
    /**
     *Kundenkonto valide, aber keine Zahlungsgarantie
     */
    const VALIDATED = 'VALIDATED';
    /**
     * Zahlungsgarantie
     */
    const FULL = 'FULL';
}
