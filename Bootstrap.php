<?php

/**
 * The Bootstrap class is the main entry point of any Shopware plugin.
 *
 * Short function reference
 * - install: Called a single time during (re)installation. Here you can trigger install-time actions like
 *   - creating the menu
 *   - creating attributes
 *   - creating database tables
 *   You need to return "true" or array('success' => true, 'invalidateCache' => array())
 *   in order to let the installation be successful
 *
 * - update: Triggered when the user updates the plugin. You will get passes the former version of the plugin as param
 *   In order to let the update be successful, return "true"
 *
 * - uninstall: Triggered when the plugin is reinstalled or uninstalled. Clean up your tables here.
 */

require_once 'Util.php';
// needed for CSRF Protection compatibility SW versions < 5.2
require_once __DIR__ . '/Components/CSRFWhitelistAware.php';

use Fatchip\CTPayment\CTPaymentAttributes;
use Fatchip\CTPayment\CTPaymentConfigForms;
use Fatchip\CTPayment\CTPaymentService;

class Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    /**
     * registers the custom plugin models and plugin namespaces
     */
    public function afterInit()
    {
        $this->registerCustomModels();
        $this->registerComponents();
    }

    /**
     * @return array|bool
     * @throws Exception
     */
    public function install()
    {
        $minimumVersion = $this->getInfo()['compatibility']['minimumVersion'];
        if (!$this->assertMinimumVersion($minimumVersion)) {
            throw new \RuntimeException("At least Shopware {$minimumVersion} is required");
        }

        $this->createPayments();

        $this->subscribeEvent('Enlight_Controller_Front_DispatchLoopStartup', 'onStartDispatch');
        // add amazon javascript to
        $this->subscribeEvent(
            'Theme_Compiler_Collect_Plugin_Javascript',
            'addJsFiles'
        );

        // extend order model
        $this->addAttributes('fatchipCT', 's_order_attributes', CTPaymentAttributes::orderAttributes);
        $this->addAttributes('fatchipCT', 's_user_addresses_attributes', CTPaymentAttributes::orderDetailsAttributes);
        // extend address tables depending on sw version
        if ($this->assertMinimumVersion('5.2')) {
            $this->addAttributes('fatchipCT', 's_user_addresses_attributes', CTPaymentAttributes::userAddressAttributes);
        } else {
            $this->addAttributes('fatchipCT', 's_user_billingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
            $this->addAttributes('fatchipCT', 's_user_shippingaddress_attributes', CTPaymentAttributes::userAddressAttributes);
        }

        $this->createTables();
        //$this->updateSchema();
        $this->createConfig();
        $this->createRiskRules();
        return ['success' => true, 'invalidateCache' => ['backend', 'config', 'proxy']];
    }

    /**
     * Registers the snippet directory, needed for backend snippets
     */
    public function registerSnippets()
    {
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
    }

    /**
     * Register the custom model dir
     */
    protected function registerCustomModels()
    {
        Shopware()->Loader()->registerNamespace(
            'Shopware\CustomModels',
            $this->Path() . 'Models/'
        );
    }

    /**
     * Registers the namespaces that are used by the plugin components
     */
    private function registerComponents()
    {

       Shopware()->Loader()->registerNamespace(
            'Shopware\FatchipCTPayment',
            $this->Path()
        );


        Shopware()->Loader()->registerNamespace(
            'Fatchip',
            $this->Path() . 'Components/Api/lib/'
        );
    }

    public function createRiskRules()
    {
        $this->createComputopRiskRule('fatchip_computop_easycredit',
            'ORDERVALUELESS', '200');

        $this->createComputopRiskRule('fatchip_computop_przelewy24',
            'CURRENCIESISOISNOT', 'PLN');

        $this->createComputopRiskRule('fatchip_computop_ideal',
            'BILLINGLANDISNOT', 'NL');
    }

    public function addJsFiles(Enlight_Event_EventArgs $args)
    {
        $jsFiles = [
            $this->Path() . 'Views/responsive/frontend/_resources/javascript/fatchipCTAmazon.js',
        ];
        return new \Doctrine\Common\Collections\ArrayCollection($jsFiles);
    }

    /**
     * create tables
     */
    protected function createTables()
    {
        $em = $this->Application()->Models();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);

        try {
            $schemaTool->createSchema(array(
                $em->getClassMetadata('Shopware\CustomModels\FatchipCTIdeal\FatchipCTIdealIssuers'),
            ));
        } catch (\Exception $e) {
            // ignore
        }
    }

    /**
     * This callback function is triggered at the very beginning of the dispatch process and allows
     * us to register additional events on the fly. This way you won't ever need to reinstall you
     * plugin for new events - any event and hook can simply be registered in the event subscribers
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $this->registerComponents();
        //$this->registerTemplateDir();
        $this->registerSnippets();

        //TODO: Schauen ob wirklich gebraucht wird.
        $container = Shopware()->Container();

        $subscribers = [
            new Shopware\FatchipCTPayment\Subscribers\ControllerPath($this->Path()),
            new Shopware\FatchipCTPayment\Subscribers\Service(),
            new Shopware\FatchipCTPayment\Subscribers\Utils(),
            new Shopware\FatchipCTPayment\Subscribers\Templates($this),
            new Shopware\FatchipCTPayment\Subscribers\Checkout(),
            new Shopware\FatchipCTPayment\Subscribers\BackendRiskManagement($container),
            new Shopware\FatchipCTPayment\Subscribers\FrontendRiskManagement($container),
            new Shopware\FatchipCTPayment\Subscribers\BackendOrder($container),
        ];

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
    }

    /**
     * Updates the database scheme from an existing doctrine model.
     */
    protected function updateSchema()
    {

        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = $this->getModelClasses($em);

        try {
            // $tool->updateSchema($classes, true);
        } catch (Exception $e) {
            // ignore
        }
    }


    /***
     *  Creates the settings page for this plugin.
     */
    private function createConfig()
    {
        $this->createGeneralConfigForm(CTPaymentConfigForms::formGeneralTextElements);

        $this->createCreditCardConfigForm(CTPaymentConfigForms::formCreditCardSelectElements, CTPaymentConfigForms::formCreditCardNumberElements);


        // ideal and Sofort
        $this->Form()->setElement('button', 'fatchip_computop_ideal_button', [
            'label' => '<strong>iDeal Banken aktualisieren <strong>',
            'handler' => "function(btn) {" . file_get_contents(__DIR__ . '/Views/common/backend/ideal/ideal_button_handler.js') . "}"
        ]);

        $this->createFormSelectElements(CTPaymentConfigForms::formIdealSelectElements);

        // Mobilepay
        $this->createFormTextElements(CTPaymentConfigForms::formMobilePayBooleanElements);

        $this->createPayDirektConfigForm(CTPaymentConfigForms::formPayDirektTextElements, CTPaymentConfigForms::formPayDirektSelectElements, CTPaymentConfigForms::formPayDirektNumberElements );

        //paypal
        $this->createFormSelectElements(CTPaymentConfigForms::formPayPalSelectElements);

        //amazon
        $this->createAmazonPayConfigForm(CTPaymentConfigForms::formAmazonTextElements, CTPaymentConfigForms::formAmazonSelectElements);

        //rating
        $this->createFormTextElements(CTPaymentConfigForms::formBonitaetElements);

        //CRIF-Bonitätsprüfung. Globally set to inactive, Quickcheck or Creditcheck

        $this->createFormSelectElements(CTPaymentConfigForms::formBonitaetSelectElements);
    }

    /**
     * create payment methods
     */
    protected function createPayments()
    {
        /** @var CTPaymentService $service */
        $service = new CTPaymentService(null);
        $paymentMethods = $service->getPaymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            if ($this->Payments()->findOneBy(array('name' => $paymentMethod['name']))) {
                continue;
            }

            $payment = array(
                'name' => $paymentMethod['name'],
                'description' => $paymentMethod['description'],
                'action' => $paymentMethod['action'],
                'active' => 0,
                'position' => $paymentMethod['position'],
                'template' => $paymentMethod['template'],
                'additionalDescription' => $paymentMethod['additionalDescription'],
            );

            $paymentObject = $this->createPayment($payment);

            if (!empty($paymentMethod['countries'])) {
                $this->restrictPaymentShippingCountries($paymentObject, $paymentMethod['countries']);
            }
        }
    }

    protected function restrictPaymentShippingCountries($paymentObject, $countries)
    {
        $countryCollection = new Doctrine\Common\Collections\ArrayCollection();
        foreach ($countries as $countryIso) {
            $country =
                Shopware()->Models()->getRepository(Shopware\Models\Country\Country::class)->findOneBy(['iso' => $countryIso]);
            if ($country !== null) {
                $countryCollection->add($country);
            }
        }
        $paymentObject->setCountries($countryCollection);
    }

    protected function createComputopRiskRule($paymentName, $rule1, $value1)
    {
        /** @var \Shopware\Components\Model\ModelManager $manager */
        $manager = $this->get('models');
        $payment = $this->getPaymentObjByName($paymentName);

        // ToDo refactor rules array in case we have more rules for other payments
        $rules = [];
        $valueRule = new \Shopware\Models\Payment\RuleSet();
        $valueRule->setRule1($rule1);
        $valueRule->setValue1($value1);
        $valueRule->setRule2('');
        $valueRule->setValue2('');
        $valueRule->setPayment($payment);
        $rules[] = $valueRule;

        // only add risk rules if no rules are set

        if ($payment->getRuleSets() === null ||
            $payment->getRuleSets()->count() === 0) {
            $payment->setRuleSets($rules);
            foreach ($rules as $rule) {
                $manager->persist($rule);
            }
            $manager->flush($payment);
        }
    }

    private function getPaymentObjByName($paymentName)
    {
        /** @var Shopware\Models\Payment\Payment $result */
        $result = $this->Payments()->findOneBy(
            [
                'name' => [
                    $paymentName,
                ]
            ]
        );
        return $result;
    }

    /**
     * @param string $prefix
     * @param string $table
     * @param array $attributes
     */
    private function addAttributes($prefix, $table, $attributes)
    {
        foreach ($attributes as $name => $attribute) {
            try {
                $this->get('models')->addAttribute($table, $prefix, $name, $attribute['type']);

            } catch (Exception $e) {
            }
        }

        $this->get('models')->generateAttributeModels(
            [
                $table
            ]
        );
    }

    private function createGeneralConfigForm($formGeneralTextElements)
    {
        $this->createFormTextElements($formGeneralTextElements);
    }

    private function createCreditCardConfigForm($formCreditCardSelectElements, $formCreditCardNumberElements )
    {
        $this->createFormSelectElements($formCreditCardSelectElements);
        $this->createFormTextElements($formCreditCardNumberElements);
    }

    private function createPayDirektConfigForm($formPayDirektTextElements, $formPayDirektSelectElements, $formPayDirektNumberElements )
    {
        $this->createFormTextElements($formPayDirektTextElements);
        $this->createFormSelectElements($formPayDirektSelectElements);
        $this->createFormTextElements($formPayDirektNumberElements);
    }

    private function createAmazonPayConfigForm($formAmazonTextElements, $formAmazonSelectElements )
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
            $this->Form()->setElement($element['type'], $element['name'], array(
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
            $this->Form()->setElement($element['type'], $element['name'], array(
                'value' => $element['value'],
                'label' => $element['label'],
                'required' => $element['required'],
                'editable' => $element['editable'],
                'store' => $element['store'],
                'description' => $element['description'],
            ));
        }
    }

    /**
     * Returns plugin info
     *
     * @return array
     * @throws Exception
     */
    public function getInfo()
    {
        $logo = base64_encode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'logo.png'));
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if (!$info) {
            throw new Exception('The plugin has an invalid version file.');
        }

        $info['label'] = $info['label']['de'];
        $info['version'] = $info['currentVersion'];
        $info['description'] = '<p><img src="data:image/png;base64,' . $logo . '" /></p> '
            . file_get_contents(__DIR__ . '/README.html');

        return $info;
    }

    /**
     * Returns the current version number
     *
     * @return string
     * @throws Exception
     */
    public function getVersion()
    {
        return $this->getInfo()['currentVersion'];
    }

    /**
     * Returns the plugin display name
     *
     * @return string
     * @throws Exception
     */
    public function getLabel()
    {
        return $this->getInfo()['label']['de'];
    }

    /**
     * Returns the plugin solution name
     *
     * @return string
     * @throws Exception
     */
    public function getSolutionName()
    {
        return $this->getInfo()['solution_name'];
    }

    /**
     * Returns the capabilities of the plugin
     *
     * @return array
     */
    public function getCapabilities()
    {
        return [
            'install' => true,
            'update' => true,
            'enable' => true,
            'secureUninstall' => true,
        ];
    }

    /**
     * @return array
     */
    public function enable()
    {
        return $this->invalidateCaches(true);
    }

    /**
     * @return array
     */
    public function disable()
    {
        return $this->invalidateCaches(true);
    }

    /**
     * Uninstalls the plugin
     *
     * @return array
     */
    public function uninstall()
    {
        return $this->disable();
    }

    /**
     * Secure uninstall plugin method
     *
     * @return array
     */
    public function secureUninstall()
    {
        return $this->disable();
    }

    /**
     * Updates the plugin
     *
     * @param string $oldVersion
     * @return array
     */
    public function update($oldVersion)
    {
        return $this->invalidateCaches(true);
    }

    /**
     * @param bool $return
     * @return array
     */
    protected function invalidateCaches($return)
    {
        return [
            'success' => $return,
            'invalidateCache' => [
                'backend',
                'config',
                'frontend',
                'http',
                'proxy',
                'router',
                'template',
                'theme',
            ],
        ];
    }

}
