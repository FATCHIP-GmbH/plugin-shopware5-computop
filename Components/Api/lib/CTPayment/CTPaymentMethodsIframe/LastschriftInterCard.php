<?php

namespace Fatchip\CTPayment\CTPaymentMethodsIframe;

use Fatchip\CTPayment\CTPaymentMethodIframe;

class LastschriftInterCard extends Lastschrift
{
    /**
     * Bestimmt Art und Zeitpunkt der Buchung (engl. Capture).
     * AUTO: Buchung so-fort nach Autorisierung (Standardwert).
     * MANUAL: Buchung erfolgt durch den Händler.
     * <Zahl>: Verzögerung in Stunden bis zur Buchung (ganze Zahl; 1 bis 696).
     *
     * @var string
     */
    private $capture; //AUTO, MANUAL, ZAHL

    /**
     * für SEPA: SEPA-Mandatsnummer (Pflicht bei SEPA) sollte eindeutig sein, ist nicht case-sensitive
     *
     * @var string
     */
    private $mandateID;

    /**
     * Kundennummer beim Händler
     *
     * @var string
     */
    private $customerID;

    /**
     * Vorname der Rechnungsanschrift
     *
     * @var string
     */
    private $bdFirstName;

    /**
     *  Nachname der Rechnungsanschrift
     *
     * @var string
     */
    private $bdLastName;

    /**
     * Straßenname der Rechnungsanschrift
     *
     * @var string
     */
    private $bdStreet;

    /**
     * Hausnummer der Rechnungsanschrift
     *
     * @var string
     */
    private $bdStreetNr;

    /**
     * Postleitzahl der Rechnungsanschrift
     *
     * @var int
     */
    private $bdZip;

    /**
     * Ortsname der Rechnungsanschrift
     *
     * @var string
     */
    private $bdCity;
}
