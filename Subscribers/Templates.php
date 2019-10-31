<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Theme\LessDefinition;
use \Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap as Bootstrap;

/**
 * Class Templates
 *
 * @package Shopware\Plugins\FatchipCTPayment\Subscribers
 */
class Templates implements SubscriberInterface
{
    /**
     * Path.
     * @var string $path
     */
    private $path;
    /**
     * Templatemanager.
     * @var \Enlight_Template_Manager $templateManager
     */
    private $templateManager;

    /**
     * Templates constructor.
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->path = $bootstrap->Path();
        $this->templateManager = $bootstrap->get('template');
    }

    /**
     * returns array with all subsribed events.
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure' => 'onPostDispatchSecure',
            // used for menu logos
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'addTemplateDir',
            'Theme_Compiler_Collect_Plugin_Less' => 'onThemeCompilerCollectPluginLess'
        );
    }

    /**
     * ToDO update Docblock
     * Selectes the template directory based on the requested module as well as the
     * template version, when requesting the frontend. Backend and API requests
     * as well as frontend requests with a template version < 3 use the 'old'
     * emotion templates, whereas frontend requests with a template version >= 3
     * use the new responsive theme templates.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchSecure(\Enlight_Event_EventArgs $args)
    {
        $subject = $args->getSubject();
        $pluginConfig = Shopware()->Plugins()->Frontend()->FatchipCTPayment()->Config()->toArray();

        //TODO: move to debit subscriber
        $subject->View()->FatchipCTPaymentIbanAnon = $pluginConfig['lastschriftAnon'] == 'Aus' ? 0 : 1;

        // Add the template directory for the used template type
        $this->templateManager->addTemplateDir(
            $this->path . 'Views/' . 'responsive' . '/'
        );
    }

    /**
     * adds template directory
     * @param \Enlight_Event_EventArgs $args
     */
    public function addTemplateDir(\Enlight_Event_EventArgs $args)
    {
        // Add the template directory for the used template type
        $this->templateManager->addTemplateDir(
            $this->path . 'Views/' . 'responsive' . '/'
        );
    }

    /**
     * Adds all.less to less definistion
     * @return LessDefinition
     */
    public function onThemeCompilerCollectPluginLess()
    {
        return new LessDefinition(
            [],
            [__DIR__ . '/../Views/frontend/_public/src/less/all.less']
        );
    }
}
