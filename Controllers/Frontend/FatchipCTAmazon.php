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

use Fatchip\CTPayment\CTEnums\CTEnumStatus;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTAmazon
 *
 * @category  Payment_Controller
 * @package   Computop_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 Computop
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.computop.com
 */
class Shopware_Controllers_Frontend_FatchipCTAmazon extends Shopware_Controllers_Frontend_FatchipCTPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'AmazonPay';

    /**
     * Success action method.
     *
     * Called after Computop redirects to SuccessURL
     * If everything is OK, order is created with status Paid, TransactionIDs are saved,
     * RefNr is updated and user is redirected to finish page
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $response = $this->ctSetAndConfirmOrderDetails();
        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getOrderid(),
                    self::PAYMENTSTATUSPAID
                );
                $this->saveTransactionResult($response);
                $this->updateRefNrWithComputopFromOrderNumber($orderNumber);
                $this->redirect(['controller' => 'FatchipCTAmazonCheckout', 'action' => 'finish']);
                break;
            default:
                // ToDO Test this after reloading confirm page
                $this->forward('failure');
                break;
        }
    }

    /**
     * Cancel action method.
     *
     * If an error occurs in the Computop call, and FailureURL is set, user is redirected to this
     * Reads error message from Response and redirects to Amazon Register Page
     *
     * @return void
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

    /**
     * Finishes the order by calling the computop api.
     *
     * @return \Fatchip\CTPayment\CTResponse
     * @throws Exception
     */
    public function ctSetAndConfirmOrderDetails()
    {
        $session = Shopware()->Session();
        $orderDesc = "Test";

        $payment = $this->paymentService->getPaymentClass('AmazonPay', $this->config);
        $requestParams = $payment->getAmazonSCOParams(
            $session->offsetGet('fatchipCTPaymentPayID'),
            $session->offsetGet('fatchipCTPaymentTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName(),
            $orderDesc,
            $session->offsetGet('fatchipCTAmazonReferenceID')
        );
        $requestParams['EtId'] = $this->getUserDataParam();
        $response = $this->plugin->callComputopService($requestParams, $payment, 'SCO', $payment->getCTPaymentURL());
        return $response;
    }
}


