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

require_once 'FatchipCTPayment.php';

use Fatchip\CTPayment\CTEnums\CTEnumStatus;


/**
 * Class Shopware_Controllers_Frontend_FatchipCTAmazon
 */
class Shopware_Controllers_Frontend_FatchipCTAmazon extends Shopware_Controllers_Frontend_FatchipCTPayment
{
    /**
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $response = $this->ctSetAndConfirmOrderDetails();
        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $this->saveOrder(
                    $response->getTransID(),
                    $response->getOrderid(),
                    self::PAYMENTSTATUSPAID
                );
                $this->redirect(['controller' => 'FatchipCTAmazonCheckout', 'action' => 'finish']);
                break;
            default:
                // ToDO Test this after reloading confirm page
                $this->forward('failure');
                break;
        }
    }

    /**
     * @return void
     * Cancel action method
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $ctError = [];

        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $ctError['CTErrorMessage'] = self::ERRORMSG . $response->getDescription();
        $ctError['CTErrorCode'] = $response->getCode();
        return $this->forward('index', 'FatchipCTAmazonRegister', null, ['CTError' => $ctError]);
    }

    public function ctSetAndConfirmOrderDetails()
    {
        $session = Shopware()->Session();
        $orderDesc = "Test";

        /** @var \Fatchip\CTPayment\CTPaymentMethods\AmazonPay $payment */
        $payment = $this->paymentService->getPaymentClass('AmazonPay', $this->config);
        $requestParams = $payment->getAmazonSCOParams(
            $session->offsetGet('fatchipCTPaymentPayID'),
            $session->offsetGet('fatchipCTPaymentTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName(),
            $orderDesc,
            $session->offsetGet('fatchipCTAmazonReferenceID')
        );
        $response = $this->plugin->callComputopService($requestParams, $payment, 'SCO');
        return $response;
    }
}


