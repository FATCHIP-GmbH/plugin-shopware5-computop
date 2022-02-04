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
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */

require_once 'FatchipFCSPayment.php';

use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTPaymentMethods\KlarnaPayments;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSKlarna.
 *
 * @category   Payment_Controller
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcash.com
 */
class Shopware_Controllers_Frontend_FatchipFCSKlarnaPayments extends Shopware_Controllers_Frontend_FatchipFCSPayment
{
    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'KlarnaPayments';

    protected function storeAuthorizationTokenAction()
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();

        $tokenExt = $this->request->getParam('authorizationToken');

        $this->session->offsetSet('FatchipFCSKlarnaPaymentTokenExt', $tokenExt);
    }

    public function getAccessTokenAction()
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();

        $data = $this->session->offsetGet('FatchipFCSKlarnaAccessToken');
        $encoded = json_encode($data);

        echo $encoded;
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
        $ctOrder = $this->utils->createCTOrder();

        /** @var KlarnaPayments $payment */
        $payment = $this->paymentService->getPaymentClass(
            $this->paymentClass
        );

        $CTPaymentURL = $payment->getCTPaymentURL();

        $payId = $this->session->offsetGet('FatchipFCSKlarnaPaymentSessionResponsePayID');
        $transId = $this->session->offsetGet('FatchipFCSKlarnaPaymentSessionResponseTransID');
        $tokenExt = $this->session->offsetGet('FatchipFCSKlarnaPaymentTokenExt');
        $this->session->offsetUnset('FatchipFCSKlarnaPaymentTokenExt');

        $ctRequest = $payment->cleanUrlParams($payment->getKlarnaOrderRequestParams($payId, $transId, $ctOrder->getAmount(), $ctOrder->getCurrency(), $tokenExt));
        $response = null;

        $ctRequest['EtiId'] = $this->utils->getUserDataParam();
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
                $ctError['CTErrorMessage'] = Shopware()->Snippets()
                    ->getNamespace('frontend/FatchipFCSPayment/translations')
                    ->get('errorGeneral'); // . $response->getDescription();
                $ctError['CTErrorCode'] = ''; //$response->getCode();
                return $this->forward('shippingPayment', 'checkout', null, array('CTError' => $ctError));

                break;
        }
    }
}
