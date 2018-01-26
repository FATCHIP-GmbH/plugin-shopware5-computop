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

class Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    private $formGeneralTextElements =
        [
            'merchantID' => [
                'name' => 'merchantID',
                'type' => 'text',
                'value' => '',
                'label' => 'MerchantID',
                'required' => true,
                'description' => '',
            ],
            'mac' => [
                'name' => 'mac',
                'type' => 'text',
                'value' => '',
                'label' => 'MAC',
                'required' => true,
                'description' => '',
            ],
            'blowfishPassword' => [
                'name' => 'blowfishPassword',
                'type' => 'text',
                'value' => '',
                'label' => 'Blowfish Password',
                'required' => true,
                'description' => '',
            ],
        ];

    private $formCreditCardSelectElements =
        [
            'creditCardCaption' => [
                'name' => 'creditCardCaption',
                'type' => 'select',
                'value' => 'AUTO',
                'label' => 'Kreditkarte - Caption Methode',
                'required' => true,
                'editable' => false,
                'store' =>
                    [
                        ['AUTO', 'Automatisch'],
                        ['MANUAL', 'Manuell'],
                        ['DELAYED', 'Verzögert'],
                    ],
                'description' => '',
            ],
            'creditCardAcquirer' => [
                'name' => 'creditCardAcquirer',
                'type' => 'select',
                'value' => 'GICC',
                'label' => 'Kreditkarte - Acquirer',
                'required' => 'true',
                'editable' => false,
                'store' =>
                    [
                        ['GICC', 'GICC: Concardis, B+S Card Service, EVO Payments, American Express, Elavon, SIX Payment Service'],
                        ['CAPN', 'CAPN: American Express'],
                        ['Omnipay', 'Omnipay: EMS payment solutions, Global Payments, Paysquare'],
                    ],
                'description' => '',
            ],
        ];

    private $formCreditCardNumberElements =
        [
            'creditCardDelay' => [
                'name' => 'creditCardDelay',
                'type' => 'number',
                'value' => '1',
                'label' => 'creditCardDelay',
                'required' => true,
                'description' => 'Verzögerung in Stunden wenn als Caption Methode "Verzögert" gewählt wurde',
            ],
        ];

    private $formIdealSelectElements =
        [
            'idealDirektOderUeberSofort' => [
                'name' => 'idealDirektOderUeberSofort',
                'type' => 'select',
                'value' => 'DIREKT',
                'label' => 'iDEAL - iDEAL Direkt oder über Sofort',
                'required' => true,
                'editable' => false,
                'store' =>
                    [
                        ['DIREKT', 'iDEAL Direkt'],
                        ['SOFORT', 'via Sofort'],
                    ],
                'description' => '',
            ],
            'lastschriftDienst' => [
                'name' => 'lastschriftDienst',
                'type' => 'select',
                'value' => 'DIREKT',
                'label' => 'Lastschrift - Anbinden über Dienst',
                'required' => true,
                'editable' => false,
                'store' =>
                    [
                        ['DIREKT', 'Direktanbindung'],
                        ['EVO', 'EVO Payments'],
                        ['INTERCARD', 'Intercard'],
                    ],
                'description' => '',
            ],
        ];

    private $formPayDirektTextElements =
        [
            'payDirektShopApiKey' => [
                'name' => 'payDirektShopApiKey',
                'type' => 'text',
                'value' => '',
                'label' => 'Paydirekt - Shop Api Key',
                'required' => true,
                'description' => '',
            ],
        ];

    private $formPayDirektSelectElements =
        [
            'payDirektCaption' => [
                'name' => 'payDirektCaption',
                'type' => 'select',
                'value' => 'AUTO',
                'label' => 'Paydirekt Capture Modus',
                'required' => true,
                'editable' => false,
                'store' =>
                    [
                        ['AUTO', 'Automatisch'],
                        ['MANUAL', 'Manuell'],
                        ['DELAYED', 'Verzögert'],
                    ],
                'description' => '',
            ],
        ];

    private $formPayDirektNumberElements =
        [
            'payDirektCardDelay' => [
                'name' => 'payDirektCardDelay',
                'type' => 'number',
                'value' => '1',
                'label' => 'Paydirekt - Verzögerung Einzug',
                'required' => true,
                'description' => 'Verzögerung in Stunden wenn als Caption Methode "Verzögert" gewählt wurde',
            ],
        ];

    private $formPayPalSelectElements =
        [
            'paypalCaption' => [
                'name' => 'paypalCaption',
                'type' => 'select',
                'value' => 'AUTO',
                'label' => 'Paypal - Caption Methode',
                'required' => true,
                'editable' => false,
                'store' =>
                    [
                        ['AUTO', 'Automatisch'],
                        ['MANUAL', 'Manuell'],
                    ],
                'description' => 'bestimmt, ob der angefragte Betrag sofort oder erst später abgebucht wird. <br>
                                  <b>Wichtig:<br>Bitte kontaktieren Sie den Computop Support für Manual, um die unterschiedlichen Einsatzmöglichkeiten abzuklären.</b>',
            ],
        ];

    private $formBonitaetElements =
        [
            'bonitaetusereturnaddress' => [
                'name' => 'bonitaetusereturnaddress',
                'type' => 'boolean',
                'value' => false,
                'label' => 'Bonitätsprüfung - Zurückgelieferte Adressdaten verwenden',
                'required' => true,
                'description' => '',
            ],
            'bonitaetinvalidateafterdays' => [
                'name' => 'bonitaetinvalidateafterdays',
                'type' => 'number',
                'value' => '30',
                'label' => 'Bonitätsprüfung - Wiederholen nach wieviele Tage',
                'required' => true,
                'description' => 'Verzögerung in Stunden wenn als Caption Methode "Verzögert" gewählt wurde',
            ],
        ];

    private $formMobilePayBooleanElements =
        [
            'mobilePaySendMobileNr' => [
                'name' => 'mobilePaySendMobileNr',
                'type' => 'boolean',
                'value' => false,
                'label' => 'MobilePay - Handynummer übermitteln',
                'required' => false,
                'description' => '',
            ],
        ];


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
     * registers the custom plugin models and plugin namespaces
     */
    public function afterInit()
    {
        $this->registerCustomModels();
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

        $this->addAttributes();

        $this->createTables();

        //$this->updateSchema();
        $this->createConfig();

        // payment specific risk rules
        $this->createEasyCreditRiskRule();

        return ['success' => true, 'invalidateCache' => ['backend', 'config', 'proxy']];
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

    /**
     * Registers the namespaces that are used by the plugin components
     */
    public function registerComponents()
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware\FatchipCTPayment',
            $this->Path()
        );
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
            new \Shopware\FatchipCTPayment\Subscribers\ControllerPath($this->Path()),
            new \Shopware\FatchipCTPayment\Subscribers\Service(),
            new \Shopware\FatchipCTPayment\Subscribers\Utils(),
            new \Shopware\FatchipCTPayment\Subscribers\Templates($this),
            new \Shopware\FatchipCTPayment\Subscribers\Checkout(),
            new \Shopware\FatchipCTPayment\Subscribers\BackendRiskManagement($container),
            new \Shopware\FatchipCTPayment\Subscribers\FrontendRiskManagement($container),
        ];

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
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
        // general
        $this->createFormTextElements($this->formGeneralTextElements);

        // credit cards
        $this->createFormSelectElements($this->formCreditCardSelectElements);
        $this->createFormTextElements($this->formCreditCardNumberElements);

        // ideal and Sofort
        $this->Form()->setElement('button', 'fatchip_computop_ideal_button', [
            'label' => '<strong>iDeal Banken aktualisieren <strong>',
            'handler' => "function(btn) {" . file_get_contents(__DIR__ . '/Views/common/backend/ideal/ideal_button_handler.js') . "}"
        ]);

        $this->createFormSelectElements($this->formIdealSelectElements);

        // Mobilepay
        $this->createFormTextElements($this->formMobilePayBooleanElements);

        //paydirekt
        $this->createFormTextElements($this->formPayDirektTextElements);
        $this->createFormSelectElements($this->formPayDirektSelectElements);
        $this->createFormTextElements($this->formPayDirektNumberElements);

        //paypal
        $this->createFormSelectElements($this->formPayPalSelectElements);

        //rating
        $this->createFormTextElements($this->formBonitaetElements);

        //CRIF-Bonitätsprüfung. Globally set to inactive, Quickcheck or Creditcheck
        $this->Form()->setElement('select', 'crifmethod', array(
            'value' => 'inactive',
            'store' => array(
                array('inactive', 'Inkativ'),
                array('QuickCheck', 'QuickCheck'),
                array('CreditCheck', 'CreditCheck'),
            ),
            'label' => 'Bonitätsprüfung CRIF',
            'required' => true,
            'editable' => false,
        ));
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
     * create payment methods
     */
    protected function createPayments()
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Components/Api/vendor/autoload.php';
        /** @var \Fatchip\CTPayment\CTPaymentService $service */
        $service = new \Fatchip\CTPayment\CTPaymentService(null);
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
                'additionalDescription' => '',

            );

            if (!is_null($paymentMethod['template'])) {
                $payment['template'] = $paymentMethod['template'];
            }

            $paymentObject = $this->createPayment($payment);
            if (!empty($paymentMethod['countries'])) {
                $countryCollection = new Doctrine\Common\Collections\ArrayCollection();
                foreach($paymentMethod['countries'] as $iso) {
                    $country = Shopware()->Models()->getRepository('Shopware\Models\Country\Country')->findOneBy(['iso'=> $iso]);
                    if ($country != null) {
                        $countryCollection->add($country);
                    }
                }
                $paymentObject->setCountries($countryCollection);
            }
        }
    }

    protected function createEasyCreditRiskRule()
    {
        /** @var \Shopware\Components\Model\ModelManager $manager */
        $manager = $this->get('models');
        $payment = $this->getEasyCreditPayment();

        // ToDo refactor rules array in case we have more rules for other payments
        $rules = [];
        $valueRule = new \Shopware\Models\Payment\RuleSet();
        $valueRule->setRule1('ORDERVALUELESS');
        $valueRule->setValue1('200');
        $valueRule->setRule2('');
        $valueRule->setValue2('');
        $valueRule->setPayment($payment);
        $rules[] = $valueRule;

        // only add risk rules if no rules are set

        if ($payment->getRuleSets() !== null &&
            $payment->getRuleSets()->count() === 0)
        {
            $payment->setRuleSets($rules);
            foreach ($rules as $rule) {
                $manager->persist($rule);
            }
            $manager->flush($payment);
        }
    }

    private function getEasyCreditPayment()
    {
        /** @var Shopware\Models\Payment\Payment $result */
        $result = $this->Payments()->findOneBy(
            [
                'name' => [
                    'fatchip_computop_easycredit',
                ]
            ]
        );
        return $result;
    }

    /**
     * extend shpoware models with COMPUTOP specific attributes
     */
    protected function addAttributes()
    {
        $prefix = 'fatchipc_computop';
        $util = new \Shopware\FatchipCTPayment\Util();

        $tables = $util->fcComputopAttributeExtensionsArray($this->getId());

        /** @var \Shopware\Bundle\AttributeBundle\Service\CrudService $attributeService */
        $attributeService = $this->assertMinimumVersion('5.2') ?
            Shopware()->Container()->get('shopware_attribute.crud_service') : null;

        foreach ($tables as $table => $attributes) {
            foreach ($attributes as $attribute => $options) {
                $type = is_array($options) ? $options[0] : $options;
                $data = is_array($options) ? $options[1] : [];
                if ($this->assertMinimumVersion('5.2')) {
                    $attributeService->update($table, $prefix . '_' . $attribute, $type, $data);
                } else {
                    $type = $util->unifiedToSQL($type);
                    /** @noinspection PhpDeprecationInspection */
                    Shopware()->Models()->addAttribute($table, $prefix, $attribute, $type, true, null);
                }
            }
        }
        Shopware()->Models()->generateAttributeModels(array_keys($tables));

        // SW 5.2 Use Address Table instead of shipping and billing tables
        if (\Shopware::VERSION === '___VERSION___' ||
            version_compare(\Shopware::VERSION, '5.2.0', '>=')
        ) {

            $tables = $util->fcComputopAttributeExtensionsArray52();
            $attributeService = Shopware()->Container()->get('shopware_attribute.crud_service');

            foreach ($tables as $table => $attributes) {
                foreach ($attributes as $attribute => $options) {
                    $type = is_array($options) ? $options[0] : $options;
                    $data = is_array($options) ? $options[1] : [];
                    $attributeService->update($table, $prefix . '_' . $attribute, $type, $data);
                }
            }
            Shopware()->Models()->generateAttributeModels(array_keys($tables));
        }
    }
}
