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
 * PHP version 5.6, 7 , 7.1
 *
 * @category  Payment
 * @package   Computop_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 Computop
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.computop.com
 */

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
