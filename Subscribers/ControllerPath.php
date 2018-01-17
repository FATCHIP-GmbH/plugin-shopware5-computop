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

/**
 * provide paths to custom controllers
 */
class ControllerPath implements SubscriberInterface {

    /**
     * path to plugin files
     *
     * @var string
     */
    private $path;

    /**
     * inject path to plugin files
     *
     * @param string $path
     */
    public function __construct($path) {
        $this->path = $path;
    }

    /**
     * return array with all subsribed events
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents() {
        return array(
            //Frontend
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPayment'
          => 'onGetControllerPathFrontendFatchipCTPayment',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTCreditCard'
          => 'onGetControllerPathFrontendFatchipCTCreditCard',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTEasyCredit'
          => 'onGetControllerPathFrontendFatchipCTEasyCredit',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTIdeal'
          => 'onGetControllerPathFrontendFatchipCTIdeal',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTKlarna'
          => 'onGetControllerPathFrontendFatchipCTKlarna',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTLastschrift'
          => 'onGetControllerPathFrontendFatchipCTLastschrift',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTMobilepay'
          => 'onGetControllerPathFrontendFatchipCTMobilepay',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaydirekt'
          => 'onGetControllerPathFrontendFatchipCTPaydirekt',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPaypalStandard'
          => 'onGetControllerPathFrontendFatchipCTPaypalStandard',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPostFinance'
          => 'onGetControllerPathFrontendFatchipCTPostFinance',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTPrzelewy24'
          => 'onGetControllerPathFrontendFatchipCTPrzelewy24',
          'Enlight_Controller_Dispatcher_ControllerPath_Frontend_FatchipCTSofort'
          => 'onGetControllerPathFrontendFatchipCTSofort',




        );
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTPayment() {
        return $this->path . '/Controllers/Frontend/FatchipCTPayment.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTCreditCard() {
        return $this->path . '/Controllers/Frontend/FatchipCTCreditCard.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTEasyCredit() {
        return $this->path . '/Controllers/Frontend/FatchipCTEasyCredit.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTIdeal() {
        return $this->path . '/Controllers/Frontend/FatchipCTIdeal.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTKlarna() {
        return $this->path . '/Controllers/Frontend/FatchipCTKlarna.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTLastschrift() {
        return $this->path . '/Controllers/Frontend/FatchipCTLastschrift.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTMobilepay() {
        return $this->path . '/Controllers/Frontend/FatchipCTMobilepay.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTPaydirekt() {
        return $this->path . '/Controllers/Frontend/FatchipCTPaydirekt.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTPostFinance() {
        return $this->path . '/Controllers/Frontend/FatchipCTPostFinance.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTPrzelewy24() {
        return $this->path . '/Controllers/Frontend/FatchipCTPrzelewy24.php';
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontendFatchipCTSofort() {
        return $this->path . '/Controllers/Frontend/FatchipCTSofort.php';
    }








}
