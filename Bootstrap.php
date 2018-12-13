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

// needed for CSRF Protection compatibility SW versions < 5.2 lba
require_once __DIR__ . '/Components/CSRFWhitelistAware.php';

use Shopware\Plugins\FatchipCTPayment\Bootstrap\Forms;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\Attributes;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\Payments;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\Menu;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\RiskRules;
use Shopware\Plugins\FatchipCTPayment\Bootstrap\Models;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
 */
class Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * registers the custom models and plugin namespaces
     */
    public function afterInit()
    {
        $this->registerCustomModels();
        $this->registerComponents();
    }

    /**
     * plugin install method
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

        $this->registerJavascript();

        $this->subscribeEvent('Enlight_Controller_Front_DispatchLoopStartup', 'onStartDispatch');

        //$this->updateSchema();
        return ['success' => true, 'invalidateCache' => ['backend', 'config', 'proxy']];
    }

    /**
     * Registers the snippet directory, needed for backend snippets
     *
     */
    public function registerSnippets()
    {
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
    }

    /**
     * Registers the js files in less compiler
     * used by AmazonPay and PaypalExpress and CreditCard jquery plugins
     */
    public function registerJavascript()
    {
        $this->subscribeEvent(
            'Theme_Compiler_Collect_Plugin_Javascript',
            'addJsFiles'
        );
    }

    /**
     * Callback method for Event "Theme_Compiler_Collect_Plugin_Javascript"
     * adds
     * @param Enlight_Event_EventArgs $args
     * @return ArrayCollection
     */
    public function addJsFiles(Enlight_Event_EventArgs $args)
    {
        $jsFiles = [
            $this->Path() . 'Views/responsive/frontend/_resources/javascript/fatchipCTAmazon.js',
            $this->Path() . 'Views/responsive/frontend/_resources/javascript/fatchipCTPaypalExpress.js',
            $this->Path() . 'Views/responsive/frontend/_resources/javascript/fatchipCTCreditCard.js',
        ];
        return new ArrayCollection($jsFiles);
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
     * Registers namespaces used by the plugin
     * and its components
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
        $this->registerSnippets();

        //TODO: check if we should / can use the container everywehre
        // the effects are currently unknown, beware because of backward compatibility
        $container = Shopware()->Container();

        $subscribers = [
            new Shopware\Plugins\FatchipCTPayment\Subscribers\ControllerPath($this->Path()),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Service(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Utils(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Templates($this),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Checkout(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Account(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\BackendRiskManagement($container),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\FrontendRiskManagement($container),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\BackendOrder($container),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Logger\PostDispatchFrontendLogger(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\Logger\SecurePostDispatchFrontendLogger(),
            new Shopware\Plugins\FatchipCTPayment\Subscribers\PostDispatchBackendIndex(),
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
     * Returns the current plugin version number
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
     * Enable plugin method
     * @return array
     */
    public function enable()
    {
        return $this->invalidateCaches(true);
    }

    /**
     * Disable plugin method
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
        $forms = new Forms();
        $forms->createForm();
        $payments = new Payments();
        $payments->createPayments();
        return $this->invalidateCaches(true);
    }

    /**
     * invalidates all caches
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
     * this wrapper is used for logging Server requests and responses to our shopware model
     *
     * @param $requestParams
     * @param $payment
     * @param $requestType
     * @param $url
     *
     * @return \Fatchip\CTPayment\CTResponse
     * @throws Exception
     */
    public function callComputopService($requestParams, $payment, $requestType, $url){
        $log = new \Shopware\CustomModels\FatchipCTApilog\FatchipCTApilog();
        $log->setPaymentName($payment::paymentClass);
        $log->setRequest($requestType);
        $log->setRequestDetails(json_encode($requestParams));
        /** @var \Fatchip\CTPayment\CTResponse $response */
        $response =  $payment->callComputop($requestParams, $url);
        $log->setTransId($response->getTransID());
        $log->setPayId($response->getPayID());
        $log->setXId($response->getXID());
        $log->setResponse($response->getStatus());
        $log->setResponseDetails(json_encode($response->toArray()));
        Shopware()->Models()->persist($log);
        Shopware()->Models()->flush($log);
        return $response;
    }

    /**
     * this wrapper is used for logging Redirectrequests and responses to our shopware model
     *
     * @param array $requestParams
     * @param string $paymentName
     * @param string $requestType
     * @param \Fatchip\CTPayment\CTResponse $response
     *
     * @return void
     * @throws Exception
     */
    public function logRedirectParams($requestParams, $paymentName, $requestType, $response){
        $log = new \Shopware\CustomModels\FatchipCTApilog\FatchipCTApilog();
        $log->setPaymentName($paymentName);
        $log->setRequest($requestType);
        $log->setRequestDetails(json_encode($requestParams));
        $log->setTransId($response->getTransID());
        $log->setPayId($response->getPayID());
        $log->setXId($response->getXID());
        $log->setResponse($response->getStatus());
        $log->setResponseDetails(json_encode($response->toArray()));
        Shopware()->Models()->persist($log);
        Shopware()->Models()->flush($log);
    }
}
