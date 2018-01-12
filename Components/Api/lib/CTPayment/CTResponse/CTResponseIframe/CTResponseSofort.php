<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 04.12.17
 * Time: 12:16
 */

namespace Fatchip\CTPayment\CTResponse\CTResponseIframe;

use Fatchip\CTPayment\CTResponse\CTResponseIframe;

class CTResponseSofort extends CTResponseIframe
{
    /**
     * Eindeutig von Sofort GmbH vergebene TransaktionsID
     *
     * @var string
     */
    protected $TransactionID;

    /**
     * Geldeingang laut Sofort
     * <1> gewährleistet,
     * <0> nicht gewährleistet.
     * Bitte war-ten Sie bei 0 auf den Geldeingang, bevor Sie die Ware verschicken.
     *
     * @var int
     */
    protected $SecCriteria;

    /**
     * Name des Kontoinhabers
     *
     * @var string
     */
    protected $AccOwner;

    /**
     * Kontonummer des Kontoinhabers
     *
     * @var string
     */
    protected $AccNr;

    /**
     * Bankleitzahl des Kontoinhabers
     *
     * @var string
     *
     */
    protected $AccIBAN;

    /**
     * Bezeichnung der Bank des Kontoinhabers
     *
     * @var string
     */
    protected $AccBank;

    /**
     * International Bank Account Number
     *
     * @var string
     */
    protected $IBAN;

    /**
     * Bank Identifier Code
     *
     * @var string
     */
    protected $BIC;

    /**
     * nur bei Sofort Ident: Vorname
     *
     * @var string
     */
    protected $FirstName;

    /**
     * nur bei Sofort Ident: Nachname
     *
     * @var string
     */
    protected $LastName;

    /**
     * nur bei Sofort Ident: Straße
     *
     * @var string
     */
    protected $AddrStreet;

    /**
     * nur bei Sofort Ident: Wohnort
     *
     * @var string
     */
    protected $AddrCity;

    /**
     * nur bei Sofort Ident: Postleitzahl
     *
     * @var string
     */
    protected $AddrZip;

    /**
     * nur bei Sofort Ident: Geburtsdatum
     *
     * @var datetime
     */
    protected $Birthday;

    /**
     * nur im Erfolgsfall bei Sofort Ident: Alter
     *
     * @var int
     */
    protected $Age;
}
