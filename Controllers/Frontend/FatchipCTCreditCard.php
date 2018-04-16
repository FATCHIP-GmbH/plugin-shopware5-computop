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
 * Class Shopware_Controllers_Frontend_FatchipCTCreditCard.
 *
 * @category   Payment_Controller
 * @package    FatchipCTPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */
class Shopware_Controllers_Frontend_FatchipCTCreditCard extends Shopware_Controllers_Frontend_FatchipCTPayment
{

    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'CreditCard';

    /**
     *  GatewaAction is overridden for Creditcard because:
     *  1. extra param URLBack
     *  2. forward to iframe controller instead of Computop Gateway, so the Computop IFrame is shown within Shop layout
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $payment = $this->getPaymentClassForGatewayAction();
        $payment->setUrlBack($this->router->assemble(['controller' => 'FatchipCTCreditCard', 'action' => 'failure', 'forceSecure' => true]));

        $params = $payment->getRedirectUrlParams();
        $this->session->offsetSet('fatchipCTRedirectParams', $params);
        $this->forward('iframe', 'FatchipCTCreditCard', null, array('fatchipCTRedirectURL' => $payment->getHTTPGetURL($params)));
    }

    /**
     * Shows Computop Creditcard Iframe within shop layout
     *
     * @return void
     */
    public function iframeAction()
    {
        $this->view->loadTemplate('frontend/fatchipCTCreditCard/index.tpl');
        $this->view->assign('fatchipCTPaymentConfig', $this->config);
        $requestParams = $this->Request()->getParams();
        $this->view->assign('fatchipCTIframeURL', $requestParams['fatchipCTRedirectURL']);
        $this->view->assign('fatchipCTURL', $requestParams['fatchipCTURL']);
        $this->view->assign('fatchipCTErrorMessage', $requestParams['CTError']['CTErrorMessage']);
        $this->view->assign('fatchipCTErrorCode', $requestParams['CTError']['CTErrorCode']);
    }

    /**
     * Handle successful payments.
     *
     * Overridden because for Creditcards we forward to IFrameAction
     *
     * @return void
     * @throws Exception
     */
    public function successAction()
    {
        $requestParams = $this->Request()->getParams();
        // used for paynow silent mode

        $response = !empty($requestParams['response']) ? $requestParams['response'] : $this->paymentService->getDecryptedResponse($requestParams);

        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipCTRedirectParams'), $this->paymentClass, 'AUTH', $response);

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResult($response);

                $this->handleDelayedCapture($orderNumber);
                $this->updateRefNrWithComputopFromOrderNumber($orderNumber);

                $url = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'finish']);
                if (!$this->config['creditCardCaption'] === 'SILENT') {
                    $this->forward('iframe', 'FatchipCTCreditCard', null, array('fatchipCTURL' => $url));
                } else {
                    $this->redirect($url);
                }
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * Handle user cancellation.
     *
     * Overridden cause for Creditcard we forward to iframe action.
     *
     * @return void
     * @throws Exception
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();
        $ctError = [];

        $response = $this->paymentService->getDecryptedResponse($requestParams);

        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipCTRedirectParams'), $this->paymentClass, 'REDIRECT', $response);

        $ctError['CTErrorMessage'] = self::ERRORMSG . $response->getDescription();
        $ctError['CTErrorCode'] = $response->getCode();
        $url = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'shippingPayment']);
        if (!$this->config['creditCardCaption'] === 'SILENT') {
            $this->forward('iframe', 'FatchipCTCreditCard', null, ['fatchipCTURL' => $url, 'CTError' => $ctError]);
        } else {
            $this->forward('shippingPayment', 'checkout', null, ['CTError' => $ctError]);
        }
    }
}

