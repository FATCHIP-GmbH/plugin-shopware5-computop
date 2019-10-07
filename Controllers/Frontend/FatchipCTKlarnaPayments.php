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
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTPaymentMethods\KlarnaPayments;

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

    protected function storeAuthorizationTokenAction()
    {
        try {
            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        } catch (Exception $e) {
            // TODO: log
        }

        $tokenExt = $this->request->getParam('authorizationToken');

        $this->session->offsetSet('FatchipCTKlarnaPaymentTokenExt', $tokenExt);
    }

    public function getDefaultPaymentAction()
    {
        /** @var Enlight_Controller_Front $front */
        $front = $this->container->get('front');
        $front->Plugins()->ViewRenderer()->setNoRender();
        $front->Response()->setHeader('Content-Type', 'application/json');

        $defaultPayment = Shopware()->Config()->get('defaultpayment');
        echo json_encode($defaultPayment);
    }

    protected function getPaymentClassForGatewayAction()
    {
        $ctOrder = $this->createCTOrder();
        $payment = $this->paymentService->getPaymentClass(
            $this->paymentClass,
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'success', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            $this->getOrderDesc(),
            $this->getUserDataParam()
        );
        return $payment;
    }

    /**
     * GatewayAction is overridden because there is no redirect but a server to server call is made
     *
     * On success create the order and forward to checkout/finish
     * On failure forward to checkout/payment and set the error message
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        /** @var CTOrder $ctOrder */
        $ctOrder = $this->utils->createCTOrder($this->getUserData());

        /** @var KlarnaPayments $payment */
        $payment = $this->getPaymentClassForGatewayAction();

        $CTPaymentURL = $payment->getCTPaymentURL();

        $payId = $this->session->offsetGet('FatchipCTKlarnaPaymentSessionResponsePayID');
        $transId = $this->session->offsetGet('FatchipCTKlarnaPaymentSessionResponseTransID');
        $amount = $ctOrder->getAmount();
        $currency = $ctOrder->getCurrency();
        $tokenExt = $this->session->offsetGet('FatchipCTKlarnaPaymentTokenExt');
        $eventToken = 'CNO';

        $this->session->offsetUnset('FatchipCTKlarnaPaymentTokenExt');

        $payment->storeKlarnaOrderRequestParams(
            $payId,
            $transId,
            $amount,
            $currency,
            $tokenExt,
            $eventToken
        );

        $ctRequest = $payment->cleanUrlParams($payment->getKlarnaOrderRequestParams());
        $response = null;

        try {
            $response = $this->plugin->callComputopService($ctRequest, $payment, 'KLARNA', $CTPaymentURL);
        } catch (Exception $e) {
            // TODO: log
        }

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResult($response);

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);
                $this->forward('finish', 'checkout', null, ['sUniqueID' => $response->getPayID()]);
                break;
            default:
                $ctError = [];
                $ctError['CTErrorMessage'] = self::ERRORMSG; // . $response->getDescription();
                $ctError['CTErrorCode'] = ''; //$response->getCode();
                return $this->forward('shippingPayment', 'checkout', null, array('CTError' => $ctError));

                break;
        }
    }

    /**
     */
    public function getAccessTokenAction()
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();

        $paymentType = $this->Request()->getParam('paymentType');
        $data = $this->session->offsetGet('FatchipCTKlarnaAccessToken_' . $paymentType);
        $encoded = json_encode($data);

        echo $encoded;
    }
}
