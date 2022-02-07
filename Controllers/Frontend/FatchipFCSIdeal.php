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
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */

require_once 'FatchipFCSPayment.php';

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSIdeal.
 *
 * Frontend controller for Ideal
 *
 * @category   Payment_Controller
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */
class Shopware_Controllers_Frontend_FatchipFCSIdeal extends Shopware_Controllers_Frontend_FatchipFCSPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'Ideal';

    /**
     * GatewayAction is overridden because issuerID needs to be set.
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $payment = $this->getPaymentClassForGatewayAction();

        if ($this->config["idealDirektOderUeberSofort"] === 'PPRO') {
            // issuerID is not required for PPRO
        } else {
            $payment->setIssuerID($this->session->offsetGet('FatchipFirstCashIdealIssuer'));
        }

        $params = $payment->getRedirectUrlParams();
        $this->session->offsetSet('fatchipFCSRedirectParams', $params);

        $this->redirect($payment->getHTTPGetURL($params));
    }
}
