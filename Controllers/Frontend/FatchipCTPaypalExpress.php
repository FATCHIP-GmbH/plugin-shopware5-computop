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

use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Fatchip\CTPayment\CTPaymentMethods\PaypalExpress;
use Fatchip\CTPayment\CTPaymentMethodsIframe\PaypalStandard;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Dispatch\Dispatch;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTPaypalStandard
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */
class Shopware_Controllers_Frontend_FatchipCTPaypalExpress extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'PaypalExpress';

    /**
     * Forwards to confirm action
     *
     * @return void
     */
    public function indexAction()
    {
        if ($this->checkBasket() !== true) {
            $this->forward('confirm');
        }
    }

    /**
     * Gateway action method.
     *
     * User is redirected here after clicking on the paypal express checkout button
     * Redirects the user to the paypal website
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        /** @var Shopware\Models\Payment\Payment $paypalExpressPayment */
        $paypalExpressPayment = Shopware()->Models()->getRepository(Payment::class)->findOneBy(['name' => 'fatchip_computop_paypal_express']);
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        $dispatch = Shopware()->Models()->getRepository(Dispatch::class)->findOneBy(['id' => $this->request->getParam('dispatch')]);
        $selectedPayment = (int) $this->request->getParam('paymentId');
        $addsurcharges = $selectedPayment !== $paypalExpressPayment->getId();

        $taxAutoMode = Shopware()->Config()->get('sTAXAUTOMODE');
        $userData = $this->getUserData();

        if (!empty($taxAutoMode)) {
            $discount_tax = Shopware()->Modules()->Basket()->getMaxTax() / 100;
        } else {
            $discount_tax = Shopware()->Config()->get('sDISCOUNTTAX');
            $discount_tax = empty($discount_tax) ? 0 : (float)str_replace(',', '.', $discount_tax) / 100;
        }

        $basketAmount = $userData['additional']['show_net'] === true ? $basket['AmountNumeric'] : $basket['AmountNetNumeric'] * (1 + $discount_tax);
        $shippingCosts = $userData['additional']['show_net'] === true ? $this->request->getParam('shipping') : $this->request->getParam('shipping') * (1 + $discount_tax);
        $surcharge = $addsurcharges ? $paypalExpressPayment->getSurcharge() : 0.0;
        $surchargePercent = $paypalExpressPayment->getDebitPercent();
        $surchargeAmountPercent = $addsurcharges ? (($basket['AmountNetNumeric'] + $surcharge) / 100  * $surchargePercent) : 0.0;

        if ((int) $dispatch->getSurchargeCalculation() !== Dispatch::SURCHARGE_CALCULATION_AS_CART_ITEM) {
            $surcharge = $userData['additional']['show_net'] === true ? $surcharge : $surcharge  * (1 + $discount_tax);
        }
        $surchargeAmountPercent = $surchargeAmountPercent  * (1 + $discount_tax);

        $fullAmount = $basketAmount + $shippingCosts + $surcharge + $surchargeAmountPercent;

        $ctOrder = new CTOrder();
        $ctOrder->setAmount($fullAmount * 100);
        $ctOrder->setCurrency($this->getCurrencyShortName());
        // mandatory for paypalStandard
        $ctOrder->setOrderDesc($this->getOrderDesc());
        /** @var PaypalStandard $payment */
        $payment = $this->paymentService->getIframePaymentClass(
            'PaypalStandard',
            $this->config,
            $ctOrder,
            $this->router->assemble(['action' => 'return', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'failure', 'forceSecure' => true]),
            $this->router->assemble(['action' => 'notify', 'forceSecure' => true]),
            'Test',
            $this->getUserDataParam()
        );

        $payment->setPayPalMethod('shortcut');
        $payment->setNoShipping(0);
        $params = $payment->getRedirectUrlParams();

        $this->redirect($payment->getHTTPGetURL($params));
    }

    /**
     * Return action method.
     *
     * User is redirected here after returning from the paypal site
     * Forwards user to paypalExpressRegister or to failure Action
     *
     * @return void
     */
    public function returnAction()
    {
        $requestParams = $this->Request()->getParams();
        $session = Shopware()->Session();
        $response = $this->paymentService->getDecryptedResponse($requestParams);

        switch ($response->getStatus()) {
            case CTEnumStatus::AUTHORIZE_REQUEST;
                $session->offsetSet('FatchipCTPaypalExpressPayID', $response->getPayID());
                $session->offsetSet('FatchipCTPaypalExpressXID', $response->getXID());
                $session->offsetSet('FatchipCTPaypalExpressTransID', $response->getXID());

                $this->forward('register', 'FatchipCTPaypalExpressRegister', null, ['CTResponse' => $response]);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Success action method.
     *
     * Called after Computop redirects to SuccessURL
     * Order is created with status paid, transactionIDs are saved,
     * RefNr is updated and user is redirected to finish page
     *
     * @return void
     * @throws Exception
     */
    public function confirmAction()
    {
        $session = Shopware()->Session();

        /** @var PaypalExpress $payment */
        $payment = $this->paymentService->getPaymentClass($this->paymentClass);

        $requestParams = $payment->getPaypalExpressCompleteParams(
            $session->offsetGet('FatchipCTPaypalExpressPayID'),
            $session->offsetGet('FatchipCTPaypalExpressTransID'),
            $this->getAmount() * 100,
            $this->getCurrencyShortName()
        );

        $requestParams['EtId'] = $this->getUserDataParam();

        // ORDER call
        $response = $this->plugin->callComputopService($requestParams, $payment, 'ORDER', $payment->getCTPaymentURL());

        if ($response->getStatus() !== CTEnumStatus::OK) {
            // TODO: add error log
            $this->forward('failure');
        }

        $orderNumber = $this->saveOrder(
            $response->getTransID(),
            $response->getPayID(),
            self::PAYMENTSTATUSRESERVED
        );

        $this->saveTransactionResult($response);

        $customOrdernumber = $this->customizeOrdernumber($orderNumber);

        // REFNRCHANGE call
        $response = $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);

        if ($response->getStatus() !== CTEnumStatus::OK) {
            // TODO: add error log
            $this->savePaymentStatus(
                $response->getTransID(),
                $response->getPayID(),
                self::PAYMENTSTATUSREVIEWNECESSARY
            );
            $this->forward('failure');
        }

        // autoCapture handles errors during capture itself
        $this->autoCapture($customOrdernumber);

        $this->forward('finish', 'FatchipCTPaypalExpressCheckout', null, array('sUniqueID' => $response->getPayID()));
    }

    /**
     * Cancel action method.
     *
     * If an error occurs in the Computop call or user cancels on paypal page user is redirected here
     * Reads error message from response and redirects to shippingpayment page
     *
     * @return void
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $response = $this->paymentService->getDecryptedResponse($requestParams);
        $ctError = [];
        $ctError['CTErrorMessage'] = Shopware()->Snippets()
            ->getNamespace('frontend/FatchipCTPayment/translations')
            ->get('errorGeneral'); // . $response->getDescription();
        $ctError['CTErrorCode'] = ''; //$response->getCode();
        $this->forward('shippingPayment', 'checkout', null, ['CTError' => $ctError]);
    }

    /**
     * @return array
     * @deprecated in 5.6, will be protected in 5.8
     *
     * Get complete user-data as an array to use in view
     *
     */
    public function getUserData()
    {
        $system = Shopware()->System();
        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        if (!empty($userData['additional']['countryShipping'])) {
            $system->sUSERGROUPDATA = Shopware()->Db()->fetchRow('
                SELECT * FROM s_core_customergroups
                WHERE groupkey = ?
            ', [$system->sUSERGROUP]);

            $taxFree = $this->isTaxFreeDelivery($userData);
            $this->session->offsetSet('taxFree', $taxFree);

            if ($taxFree) {
                $system->sUSERGROUPDATA['tax'] = 0;
                $system->sCONFIG['sARTICLESOUTPUTNETTO'] = 1; // Old template
                Shopware()->Session()->set('sUserGroupData', $system->sUSERGROUPDATA);
                $userData['additional']['charge_vat'] = false;
                $userData['additional']['show_net'] = false;
                Shopware()->Session()->set('sOutputNet', true);
            } else {
                $userData['additional']['charge_vat'] = true;
                $userData['additional']['show_net'] = !empty($system->sUSERGROUPDATA['tax']);
                Shopware()->Session()->set('sOutputNet', empty($system->sUSERGROUPDATA['tax']));
            }
        }

        return $userData;
    }

    /**
     * Validates if the provided customer should get a tax free delivery
     *
     * @param array $userData
     *
     * @return bool
     */
    protected function isTaxFreeDelivery($userData)
    {
        if (!empty($userData['additional']['countryShipping']['taxfree'])) {
            return true;
        }

        if (empty($userData['additional']['countryShipping']['taxfree_ustid'])) {
            return false;
        }

        if (empty($userData['shippingaddress']['ustid'])
            && !empty($userData['billingaddress']['ustid'])
            && !empty($userData['additional']['country']['taxfree_ustid'])) {
            return true;
        }

        return !empty($userData['shippingaddress']['ustid']);
    }
}


