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
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

use Fatchip\CTPayment\CTOrder\CTOrder;

require_once 'FatchipCTPayment.php';


/**
 * Class Shopware_Controllers_Frontend_FatchipCTIdeal
 * Frontend controller for Ideal
 */
class Shopware_Controllers_Frontend_FatchipCTIdeal extends Shopware_Controllers_Frontend_FatchipCTPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'Ideal';

    /**
     * gatewayAction is overridden because issuerID needs to be set.
     */
    public function gatewayAction()
    {
        $payment = $this->getPaymentClassForGatewayAction();

        $payment->setIssuerID($this->session->offsetGet('FatchipComputopIdealIssuer'));
        $params = $payment->getRedirectUrlParams();
        $this->session->offsetSet('fatchipCTRedirectParams', $params);

        $this->redirect($payment->getHTTPGetURL($params));
    }
}
