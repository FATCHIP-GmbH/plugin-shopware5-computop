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
class Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Returns plugin info
     *
     * @return array
     * @throws Exception
     */
    public function getInfo()
    {
        $logo = base64_encode(file_get_contents(dirname(__FILE__) . '/logo.png'));
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
        //$this->updateSchema();
        $this->createConfig();

        return ['success' => true, 'invalidateCache' => ['backend', 'config', 'proxy']];
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

        $subscribers = [
            new \Shopware\FatchipCTPayment\Subscribers\ControllerPath($this->Path()),
            new \Shopware\FatchipCTPayment\Subscribers\Service(),
            new \Shopware\FatchipCTPayment\Subscribers\Templates($this),
            new \Shopware\FatchipCTPayment\Subscribers\Checkout(),
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
     *  hiddenmessage: The message that is shown blurry if article has not been bought
     *  calltoaction: Text that is shown below properties table to explain why properties are not readable
     */
    private function createConfig()
    {
        $this->Form()->setElement('text', 'merchantID', array(
            'value' => '',
            'label' => 'MerchantID',
            'required' => true,
        ));

        $this->Form()->setElement('text', 'mac', array(
            'value' => '',
            'label' => 'MAC',
            'required' => true,
        ));

        $this->Form()->setElement('text', 'blowfishPassword', array(
            'value' => '',
            'label' => 'Blowfish Password',
            'required' => true,
        ));

        $this->Form()->setElement('select', 'creditCardCaption', array(
            'value' => 'AUTO',
            'store' => array(array('AUTO', 'Automatisch'), array('MANUAL', 'Manuell'), array('DELAYED', 'Verzögert')),
            'label' => 'Kreditkarte - Caption Methode',
            'description' => '',
            'required' => true,
            'editable' => false,
        ));

        $this->Form()->setElement('number', 'creditCardDelay', array(
            'value' => '1',
            'label' => 'Kreditkarte - Verzögerung Einzug',
            'description' => 'Verzögerung in Stunden wenn als Caption Methode "Verzögert" gewählt wurde',
        ));

        $this->Form()->setElement('select', 'creditCardAcquirer', array(
            'value' => 'GICC',
            'store' => array(array('GICC', 'GICC: Concardis, B+S Card Service, EVO Payments, American Express, Elavon, SIX Payment Service'),
                array('CAPN', 'CAPN: American Express'), array('Omnipay', 'Omnipay: EMS payment solutions, Global Payments, Paysquare')),
            'label' => 'Kreditkarte - Acquirer',
            'required' => true,
            'editable' => false,
        ));

        $this->Form()->setElement('select', 'idealDirektOderUeberSofort', array(
            'value' => 'DIREKT',
            'store' => array(array('DIREKT', 'iDEAL Direkt'),
                array('SOFORT', 'via Sofort')),
            'label' => 'iDEAL - iDEAL Direkt oder über Sofort ',
            'required' => true,
            'editable' => false,
        ));

        $this->Form()->setElement('select', 'lastschriftDienst', array(
            'value' => 'DIREKT',
            'store' => array(array('DIREKT', 'Direktanbindung'),
                array('EVO', 'EVO Payments'), array('INTERCARD', 'Intercard'),
            ),
            'label' => 'Lastschrift - Anbinden über Dienst',
            'required' => true,
            'editable' => false,
        ));

        $this->Form()->setElement('boolean', 'mobilePaySendMobileNr', array(
            'value' => false,
            'label' => 'MobilePay - Handynummer übermitteln',
            'required' => false,
        ));

        $this->Form()->setElement('text', 'payDirektShopApiKey', array(
            'value' => '',
            'label' => 'Paydirekt - Shop Api Key',
            'required' => true,
        ));

        $this->Form()->setElement('select', 'payDirektCaption', array(
            'value' => 'AUTO',
            'store' => array(array('AUTO', 'Automatisch'), array('MANUAL', 'Manuell'), array('DELAYED', 'Verzögert')),
            'label' => 'Paydirekt - Caption Methode',
            'description' => '',
            'required' => true,
            'editable' => false,
        ));

        $this->Form()->setElement('number', 'payDirektCardDelay', array(
            'value' => '1',
            'label' => 'Paydirekt - Verzögerung Einzug',
            'description' => 'Verzögerung in Stunden wenn als Caption Methode "Verzögert" gewählt wurde',
        ));

        $this->Form()->setElement('select', 'paypalCaption', array(
            'value' => 'AUTO',
            'store' => array(array('AUTO', 'Automatisch'), array('MANUAL', 'Manuell')),
            'label' => 'Paypal - Caption Methode',
            'description' => 'bestimmt, ob der angefragte Betrag sofort oder erst später abgebucht wird. <br>
          <b>Wichtig:<br>Bitte kontaktieren Sie den Computop Support für Manual, um die unterschiedlichen Einsatzmöglichkeiten abzuklären.</b>',
            'required' => true,
            'editable' => false,
        ));

        $this->Form()->setElement('boolean', 'bonitaetusereturnaddress', array(
            'value' => false,
            'label' => 'Bonitätsprüfung - Zurückgelieferte Adressdaten verwenden',
            'required' => false,
            'description' => 'Sollen die zurückgelieferten Adressdaten die bereits bestehenden aktualisieren?'
        ));


        //For every paymentmethod, we add a setting for Bonitätsprüfung
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'Components/Api/vendor/autoload.php';
        /** @var \Fatchip\CTPayment\CTPaymentService $service */
        $service = new \Fatchip\CTPayment\CTPaymentService(null);
        $paymentMethods = $service->getPaymentMethods();
        foreach ($paymentMethods as $paymentMethod) {
            $this->Form()->setElement('select', 'bonitaet' . $paymentMethod['name'], array(
                'value' => 'inactive',
                'store' => array(
                    array('inactive', 'Inkativ'),
                    array('QuickCheckConsumer', 'QuickCheckConsumer'),
                    array('CreditCheckConsumer', 'CreditCheckConsumer'),
                    array('QuickCheckBusiness', 'QuickCheckBusiness'),
                    array('CreditCheckBusiness', 'CreditCheckBusiness>'),
                    array('IdentCheckConsumer', 'IdentCheckConsumer>'),
                ),
                'label' => 'Bonitätsprüfung ' . $paymentMethod['shortname'],
                'required' => true,
                'editable' => false,
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
                'action' => 'FatchipCTPayment',
                'active' => 0,
                'position' => $paymentMethod['position'],
                'additionalDescription' => '',
            );

            if (!is_null($paymentMethod['template'])) {
                $payment['template'] = $paymentMethod['template'];
            }
            $this->createPayment($payment);
        }
    }
}
