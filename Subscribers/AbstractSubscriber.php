<?php
/**
 * The First Cash Solution Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The First Cash Solution Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with First Cash Solution Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7 , 7.1
 *
 * @category  Payment
 * @package   First Cash Solution_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 First Cash Solution
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.firstcashsolution.de/
 */

namespace Shopware\Plugins\FatchipFCSPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Logger;
use Shopware\Plugins\FatchipFCSPayment\Util;

abstract class AbstractSubscriber implements SubscriberInterface
{
    /** @var Util $utils **/
    protected $utils;

    /**
     * FatchipFCSpayment Plugin Bootstrap Class
     * @var Shopware_Plugins_Frontend_FatchipFCSPayment_Bootstrap
     */

    protected $paymentClass;

    protected $logger;

    protected $config;

    private $router;

    protected $payment = null;

    public function __construct()
    {
        $this->utils = Shopware()->Container()->get('FatchipFCSPaymentUtils');
        $this->router = Shopware()->Front()->Router();
        $this->logger = new Logger('FatchipFCSPayment');
        $this->config = Shopware()->Plugins()->Frontend()->FatchipFCSPayment()->Config()->toArray();

        if($this->paymentClass) {
            $this->payment = Shopware()->Container()->get('FatchipFCSPaymentApiClient')->getPaymentClass('KlarnaPayments');
        }
    }
}
