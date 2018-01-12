<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 04.12.17
 * Time: 11:21
 */

namespace Fatchip\CTPayment\CTResponse\CTResponseIframe;

use Fatchip\CTPayment\CTResponse\CTResponseIframe;

class CTResponseKlarna extends CTResponseIframe
{

    /**
     * Reservierungsnummer: wird bei Reservierung (Autorisierung) zurückgegeben
     *
     * @var string
     */
    protected $RNo;

    /**
     * Rechnungsnummer: wird bei Aktivierung (Capture) zurückgegeben. Längste In-vNo bisher war 17-stellig.
     * Über folgende URL können Sie sich Packing Slips bei Klarna herunterladen:
     * https://online.klarna.com/packslips/{InvNo}.pdf
     *
     * @var string
     */
    protected $InvNo;
}
