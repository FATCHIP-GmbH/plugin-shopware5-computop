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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Bootstrap
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Fatchip\CTPayment\CTPaymentConfigForms;
use Shopware\Models\Config\Element;
use Shopware\Models\Config\Form;
use Shopware\Plugins\FatchipCTPayment\Subscribers\Frontend\Logger;

/**
 * Class Forms.
 *
 * create the shopware backend config form.
 */
class Forms extends Bootstrap
{
    /**
     * create the shopware backend config form.
     *
     * uses several helper methods to group different element types and sections.
     *
     * @see Form::setElement()
     *
     * @return void
     */
    public function createForm()
    {
        $logger = new Logger();

        // general settings
        $this->createGeneralConfigForm(CTPaymentConfigForms::formGeneralTextElements, CTPaymentConfigForms::formGeneralSelectElements);

        $this->plugin->Form()->setElement('button', 'fatchip_computop_apitest_button', [
            'label' => '<strong>API Test<strong>',
            'handler' => "function(btn) {" . file_get_contents(__DIR__ . '/../Views/common/backend/apitest/fatchip_ct_apitest_button_handler.js') . "}"
        ]);

        $this->createCreditCardConfigForm(CTPaymentConfigForms::formCreditCardSelectElements, CTPaymentConfigForms::formCreditCardNumberElements, CTPaymentConfigForms::formCreditCardTextElements);


        $this->createFormSelectElements(CTPaymentConfigForms::formIdealSelectElements);

        // ideal and sofort
        $this->plugin->Form()->setElement('button', 'fatchip_computop_ideal_button', [
            'label' => '<strong>iDeal Banken aktualisieren <strong>',
            'handler' => "function(btn) {" . file_get_contents(__DIR__ . '/../Views/common/backend/ideal/ideal_button_handler.js') . "}"
        ]);


        $this->createKlarnaConfigForm(CTPaymentConfigForms::formKlarnaTextElements);

        $this->createLastschriftConfigForm(CTPaymentConfigForms::formLastschriftSelectElements, CTPaymentConfigForms::formLastschriftNumberElements);

        // $this->createPayDirektConfigForm(CTPaymentConfigForms::formPayDirektSelectElements);

        // paypal
        $this->createFormSelectElements(CTPaymentConfigForms::formPayPalSelectElements);

        // amazon
        $this->createAmazonPayConfigForm(CTPaymentConfigForms::formAmazonTextElements, CTPaymentConfigForms::formAmazonSelectElements);

        // afterpay
        // $this->createFormSelectElements(CTPaymentConfigForms::formAfterpaySelectElements);

        // riskchecks
        $this->createFormSelectElements(CTPaymentConfigForms::formBonitaetSelectElements);
        $this->createFormTextElements(CTPaymentConfigForms::formBonitaetElements);

        try {
            $this->removeFormElements();
            $this->updateFormElements();
        } catch (\Exception $e) {
            $logger->logError('Unable to remove / update form elements:', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * used for removal of older obsolete config elements
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeFormElements() {
        $elements = [];
        $em = Shopware()->Models();

        if(!$this->plugin) {
            return;
        }

        $elements[] = $this->plugin->Form()->getElement('creditCardDelay');
        $elements[] = $this->plugin->Form()->getElement('lastschriftDelay');
        $elements[] = $this->plugin->Form()->getElement('lastschriftEvoDebitDelay');
        $elements[] = $this->plugin->Form()->getElement('payDirektCardDelay');
        $elements[] = $this->plugin->Form()->getElement('payDirektShopApiKey');
        $elements[] = $this->plugin->Form()->getElement('payDirektCaption');

        foreach ($elements as $element) {
            //$element = $em->find('Shopware\Models\Config\Element', $elementId);

            if ($element) {
                $em->remove($element);
            }
        }

        $em->flush();
    }

    /**
     * used for updating config element values
     *
     */
    public function updateFormElements()
    {
        if (!$this->plugin) {
            return;
        }

        $creditCardTemplateElement = $this->plugin->Form()->getElement('creditCardTemplate');
        $result = Shopware()->Db()->executeQuery(
            "SELECT value FROM s_core_config_values WHERE element_id = :element_id",
            [
                ':element_id' => $creditCardTemplateElement->getId()
            ]
        );
        $value = $result->fetch();
        $unserialized = unserialize($value['value']);
        if ($unserialized !== false && ($unserialized === '' || $unserialized === 'ct_responsive_ch')) {
            $result = Shopware()->Db()->executeQuery(
                "UPDATE s_core_config_values SET value = :value WHERE element_id = :element_id",
                [
                    ':value' => serialize('ct_responsive'),
                    ':element_id' => $creditCardTemplateElement->getId()
                ]
            );
        }
    }

    /**
     * create elements for the general section of the config form.
     *
     * creates Text and Select Elements.
     *
     * @param array $formGeneralTextElements
     * @param array $formGeneralSelectElements
     *
     * @return void
     */
    private function createGeneralConfigForm($formGeneralTextElements, $formGeneralSelectElements)
    {
        $this->createFormTextElements($formGeneralTextElements);
        $this->createFormSelectElements($formGeneralSelectElements);
    }

    /**
     * create elements for the creditcard section of the config form.
     *
     * creates Text and Select Elements.
     *
     * @param array $formCreditCardSelectElements
     * @param array $formCreditCardNumberElements
     * @param array $formCreditCardTextElements
     *
     * @return void
     */
    private function createCreditCardConfigForm($formCreditCardSelectElements, $formCreditCardNumberElements, $formCreditCardTextElements)
    {
        $this->createFormSelectElements($formCreditCardSelectElements);
        $this->createFormTextElements($formCreditCardNumberElements);
        $this->createFormTextElements($formCreditCardTextElements);
    }

    /**
     * create elements for the Klarna section of the config form.
     *
     * creates Text and Select Elements.
     *
     * @param array $formKlarnaTextElements
     *
     * @return void
     */
    private function createKlarnaConfigForm($formKlarnaTextElements)
    {
        $this->createFormTextElements($formKlarnaTextElements);
    }

    /**
     * create elements for the lastschrift section of the config form.
     *
     * creates Text and Select Elements.
     *
     * @param array $formLastschriftSelectElements
     * @param array $formLastschriftNumberElements
     *
     * @return void
     */
    private function createLastschriftConfigForm($formLastschriftSelectElements, $formLastschriftNumberElements)
    {
        $this->createFormSelectElements($formLastschriftSelectElements);
        $this->createFormTextElements($formLastschriftNumberElements);
    }

    /**
     * create elements for the amazonpay section of the config form.
     *
     * creates Text and Select Elements.
     *
     * @param array $formAmazonTextElements
     * @param array $formAmazonSelectElements
     *
     * @return void
     */
    private function createAmazonPayConfigForm($formAmazonTextElements, $formAmazonSelectElements)
    {
        $this->createFormTextElements($formAmazonTextElements);
        $this->createFormSelectElements($formAmazonSelectElements);
    }

    /**
     * actually creates the elements the config form.
     *
     * creates Text and Number Elements.
     *
     * @see Form::setElement()
     *
     * @param array $elements {
     *
     * @type string type   attribute type. Default ''. Accepts 'VARCHAR(integer)', 'float', 'DATE'.
     * @type array additionalInfo {
     *
     *      @type string label              backend label for the attribute. Default ''. Accepts String.
     *      @type string helpText           backend helptext for the attribute. Default ''. Accepts String.
     *      @type string displayInBackend   backend visibility. Default true. Accepts true|false.
     *
     *      }
     * }
     *
     * @return void
     */
    private function createFormTextElements($elements)
    {
        foreach ($elements as $element) {
            $this->plugin->Form()->setElement($element['type'], $element['name'], array(
                'value' => $element['value'],
                'label' => $element['label'],
                'required' => $element['required'],
                'description' => $element['description'],
                'scope' => Element::SCOPE_SHOP,
            ));
        }
    }

    /**
     * actually creates the elements the config form.
     *
     * creates Select Elements.
     *
     * @see Form::setElement()
     *
     * @param array $elements {
     *
     * @type string type   attribute type. Default ''. Accepts 'VARCHAR(integer)', 'float', 'DATE'.
     * @type array additionalInfo {
     *
     *      @type string label              backend label for the attribute. Default ''. Accepts String.
     *      @type string helpText           backend helptext for the attribute. Default ''. Accepts String.
     *      @type string displayInBackend   backend visibility. Default true. Accepts true|false.
     *
     *      }
     * }
     *
     * @return void
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
                'scope' => Element::SCOPE_SHOP,
            ));
        }
    }
}
