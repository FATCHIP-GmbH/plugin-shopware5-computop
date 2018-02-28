<?php

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Fatchip\CTPayment\CTPaymentConfigForms;

class Forms
{
    /***
     *  Creates the settings page for this plugin.
     */

    private $plugin;

    public function __construct()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
    }

    public function createForm()
    {
        // general settings
        $this->createGeneralConfigForm(CTPaymentConfigForms::formGeneralTextElements, CTPaymentConfigForms::formGeneralSelectElements);

        $this->createCreditCardConfigForm(CTPaymentConfigForms::formCreditCardSelectElements, CTPaymentConfigForms::formCreditCardNumberElements);


        // ideal and sofort
        $this->plugin->Form()->setElement('button', 'fatchip_computop_ideal_button', [
            'label' => '<strong>iDeal Banken aktualisieren <strong>',
            'handler' => "function(btn) {" . file_get_contents(__DIR__ . '/../Views/common/backend/ideal/ideal_button_handler.js') . "}"
        ]);

        $this->createLastschriftConfigForm(CTPaymentConfigForms::formLastschriftSelectElements, CTPaymentConfigForms::formLastschriftNumberElements);

        $this->createFormSelectElements(CTPaymentConfigForms::formIdealSelectElements);

        $this->createPayDirektConfigForm(CTPaymentConfigForms::formPayDirektTextElements, CTPaymentConfigForms::formPayDirektSelectElements, CTPaymentConfigForms::formPayDirektNumberElements);

        // paypal
        $this->createFormSelectElements(CTPaymentConfigForms::formPayPalSelectElements);

        // amazon
        $this->createAmazonPayConfigForm(CTPaymentConfigForms::formAmazonTextElements, CTPaymentConfigForms::formAmazonSelectElements);

        // riskchecks
        $this->createFormTextElements(CTPaymentConfigForms::formBonitaetElements);
        $this->createFormSelectElements(CTPaymentConfigForms::formBonitaetSelectElements);
    }

    private function createGeneralConfigForm($formGeneralTextElements, $formGeneralSelectElements)
    {
        $this->createFormTextElements($formGeneralTextElements);
        $this->createFormSelectElements($formGeneralSelectElements);
    }

    private function createCreditCardConfigForm($formCreditCardSelectElements, $formCreditCardNumberElements)
    {
        $this->createFormSelectElements($formCreditCardSelectElements);
        $this->createFormTextElements($formCreditCardNumberElements);
    }

    private function createLastschriftConfigForm($formLastschriftSelectElements, $formLastschriftNumberElements)
    {
        $this->createFormSelectElements($formLastschriftSelectElements);
        $this->createFormTextElements($formLastschriftNumberElements);
    }

    private function createPayDirektConfigForm($formPayDirektTextElements, $formPayDirektSelectElements, $formPayDirektNumberElements)
    {
        $this->createFormTextElements($formPayDirektTextElements);
        $this->createFormSelectElements($formPayDirektSelectElements);
        $this->createFormTextElements($formPayDirektNumberElements);
    }

    private function createAmazonPayConfigForm($formAmazonTextElements, $formAmazonSelectElements)
    {
        $this->createFormTextElements($formAmazonTextElements);
        $this->createFormSelectElements($formAmazonSelectElements);
    }

    /**
     * @param array $elements
     */
    private function createFormTextElements($elements)
    {
        foreach ($elements as $element) {
            $this->plugin->Form()->setElement($element['type'], $element['name'], array(
                'value' => $element['value'],
                'label' => $element['label'],
                'required' => $element['required'],
                'description' => $element['description'],
            ));
        }
    }

    /**
     * @param array $elements
     */
    private function createFormSelectElements($elements)
    {
        foreach ($elements as $element) {
            $this->plugin->Form()->setElement($element['type'], $element['name'], array(
                'value' => $element['value'],
                'label' => $element['label'],
                'required' => $element['required'],
                'editable' => $element['editable'],
                'store' => $element['store'],
                'description' => $element['description'],
            ));
        }
    }
}
