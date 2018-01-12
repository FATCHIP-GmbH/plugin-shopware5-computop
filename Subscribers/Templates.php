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

namespace Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use \Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap as Bootstrap;


class Templates implements SubscriberInterface
{
    /**
     * @var string $path
     */
    private $path;
    /**
     * @var Enlight_Template_Manager $templateManager
     */
    private $templateManager;

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->path = $bootstrap->Path();
        $this->templateManager = $bootstrap->get('template');
    }

    /**
     * @return array<string,string>
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure' => 'onPostDispatchSecure',
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
        // Add the template directory for the used template type
        $this->templateManager->addTemplateDir(
            $this->path . 'Views/' . 'responsive' . '/'
        );
    }
}