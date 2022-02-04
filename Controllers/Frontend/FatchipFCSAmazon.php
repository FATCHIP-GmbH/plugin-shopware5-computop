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
 * @link       https://www.firstcash.com
 */

require_once 'FatchipFCSPayment.php';

use Fatchip\CTPayment\CTEnums\CTEnumStatus;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSAmazon
 *
 * @category  Payment_Controller
 * @package   First Cash Solution_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 First Cash Solution
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.firstcash.com
 */
class Shopware_Controllers_Frontend_FatchipFCSAmazon extends Shopware_Controllers_Frontend_FatchipFCSPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'AmazonPay';

    /**
     * Success action method.
     *
     * Called after First Cash Solution redirects to SuccessURL
     * If everything is OK, order is created with status Paid, TransactionIDs are saved,
     * RefNr is updated and user is redirected to finish page
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $response = $this->ctFinishAuthorization();

        $amznStatus = $response->getAmazonstatus();

        if($amznStatus == 'Declined') {
            $this->redirect(['controller' => 'checkout', 'action' => 'cart', 'amznLogout' => true, 'amznError' => 'Declined']);
            return;
        }

        if($response->getStatus() == CTEnumStatus::OK) {
            $orderNumber = $this->saveOrder(
                $response->getTransID(),
                $response->getOrderid(),
                self::PAYMENTSTATUSPAID
            );
            $this->saveTransactionResult($response);

            $customOrdernumber = $this->customizeOrdernumber($orderNumber);
            $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);
            $this->forward('finish', 'FatchipFCSAmazonCheckout', null, array('sUniqueID' => $response->getOrderid()));
        }
        else {
            $this->redirect(['controller' => 'checkout', 'action' => 'cart', 'amznLogout' => true, 'amznError' => 'generic']);
        }
    }

    /**
     * Cancel action method.
     *
     * If an error occurs in the First Cash Solution call, and FailureURL is set, user is redirected to this
     * Reads error message from Response and redirects to Amazon Register Page
     *
     * @return void
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $ctError = [];

        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $ctError['CTErrorMessage'] = Shopware()->Snippets()
            ->getNamespace('frontend/FatchipFCSPayment/translations')
            ->get('errorGeneral'); // . $response->getDescription();
        $ctError['CTErrorCode'] = ''; //$response->getCode();
        return $this->forward('index', 'FatchipFCSAmazonRegister', null, ['CTError' => $ctError]);
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

        $payment = $this->paymentService->getPaymentClass('AmazonPay');
        $requestParams = $payment->getAmazonSCOParams(
            $session->offsetGet('fatchipFCSPaymentPayID'),
            $session->offsetGet('fatchipFCSPaymentTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName(),
            $this->getOrderDesc(),
            $session->offsetGet('fatchipFCSAmazonReferenceID')
        );
        $requestParams['EtiId'] = $this->utils->getUserDataParam();
        $response = $this->plugin->callComputopService($requestParams, $payment, 'SCO', $payment->getCTPaymentURL());
        return $response;
    }

    public function ctFinishAuthorization()
    {
        $session = Shopware()->Session();
        $orderDesc = "Test";
        $payment = $this->paymentService->getPaymentClass('AmazonPay');
        $requestParams = $payment->getAmazonATHParams(
            $session->offsetGet('fatchipFCSPaymentPayID'),
            $session->offsetGet('fatchipFCSPaymentTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName(),
            $session->offsetGet('fatchipFCSAmazonReferenceID'),
            $this->getOrderDesc()
        );
        $requestParams['EtiId'] = $this->utils->getUserDataParam();
        $response = $this->plugin->callComputopService($requestParams, $payment, 'ATH', $payment->getCTPaymentURL());

        return $response;
    }

    /**
     * Order description sent to First Cash Solution.
     *
     * Returns shopname.
     * If a paymentmethod needs a different Orderdescription, override this method.
     *
     * @return string
     */
    public function getOrderDesc()
    {
        $shopContext = $this->get('shopware_storefront.context_service')->getShopContext();
        $shopName = $shopContext->getShop()->getName();
        return $shopName;
    }
}