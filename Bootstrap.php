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

// needed for CSRF Protection compatibility SW versions < 5.2 lba
require_once __DIR__ . '/Components/CSRFWhitelistAware.php';

use Shopware\Plugins\FatchipCTPayment\Bootstrap\Forms;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\Attributes;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\Payments;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\Menu;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\RiskRules;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\Models;


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

        // Helper Classes
        $forms = new Forms();
        $attributes = new Attributes();
        $payments = new Payments();
        $menu = new Menu();
        $riskRules = new RiskRules();
        $models = new Models();

        $forms->createForm();
        $attributes->createAttributes();
        $payments->createPayments();
        $menu->createMenu();
        $riskRules->createRiskRules();
        $models->createModels();

        $this->subscribeEvent('Enlight_Controller_Front_DispatchLoopStartup', 'onStartDispatch');

        //$this->updateSchema();
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
            'Shopware\Plugins\FatchipCTPayment',
            $this->Path()
        );

        Shopware()->Loader()->registerNamespace(
            'Fatchip',
            $this->Path() . 'Components/Api/lib/'
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
            new Shopware\Plugins\FatchipCTPayment\Subscribers\ControllerPath($this->Path()),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Service(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Utils(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Templates($this),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Checkout(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\BackendRiskManagement($container),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\FrontendRiskManagement($container),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\BackendOrder($container),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Logger(),
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

    // this wrapper is used for logging requests and responses to our shopware model
    public function callComputopService($requestParams, $service, $requestType){
        $log = new \Shopware\CustomModels\FatchipCTApilog\FatchipCTApilog();
        $log->setPaymentName('AmazonPay');
        $log->setRequest($requestType);
        $log->setRequestDetails(json_encode($requestParams));
        $response =  $service->callComputop($requestParams);
        $log->setTransId($response['TransID']);
        $log->setPayId($response['PayID']);
        $log->setXId($response['XID']);
        $log->setResponse($response['Status']);
        $log->setResponseDetails(json_encode($response));
        Shopware()->Models()->persist($log);
        Shopware()->Models()->flush($log);
        return $response;
    }
}
