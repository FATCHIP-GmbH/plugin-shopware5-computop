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
 * PHP version 5.6, 7.0, 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

require_once 'FatchipCTPayment.php';

use Fatchip\CTPayment\CTPaymentMethodsIframe\KlarnaPayments;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTKlarna.
 *
 * @category   Payment_Controller
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */
class Shopware_Controllers_Frontend_FatchipCTKlarnaPayments extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'KlarnaPayments';

    protected function getKlarnaSessionAction() {
        /** @var KlarnaPayments $payment */
        $payment = $this->paymentService->getPaymentClass('KlarnaPayments', $this->config);

        $requestParams = $payment->getKlarnaSessionRequestParams(
            $session->offsetGet('fatchipCTPaymentPayID'),
            $session->offsetGet('fatchipCTPaymentTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName(),
            $this->getOrderDesc(),
            $session->offsetGet('fatchipCTAmazonReferenceID')
        );

        $response = $this->plugin->callComputopService($requestParams, $payment, 'SCO', $payment->getCTPaymentURL());
    }
}
