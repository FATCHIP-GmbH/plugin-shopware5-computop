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

use Fatchip\FCSPayment\CTEnums\CTEnumStatus;
use Monolog\Handler\RotatingFileHandler;
use Shopware\Plugins\FatchipFCSPayment\Util;

/**
 * Class Shopware_Controllers_Frontend_FatchipFCSCreditCard.
 *
 * @category   Payment_Controller
 * @package    FatchipFCSPayment
 * @subpackage Controllers/Frontend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */
class Shopware_Controllers_Frontend_FatchipFCSCreditCard extends Shopware_Controllers_Frontend_FatchipFCSPayment
{

    /**
     * {@inheritdoc}
     */
    public $paymentClass = 'CreditCard';

    /**
     * prevents CSRF Token errors
     * @return array
     */
    public function getWhitelistedCSRFActions()
    {
        $csrfActions = ['success', 'failure', 'notify', 'iframe', 'browserinfo'];

        return $csrfActions;
    }

    /**
     *  GatewaAction is overridden for Creditcard because:
     *  1. extra param URLBack
     *  2. forward to iframe controller instead of First Cash Solution Gateway, so the First Cash Solution IFrame is shown within Shop layout
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $payment = $this->getPaymentClassForGatewayAction();

        // check if user already used cc payment successfully and send
        // initialPayment true or false accordingly
        $user = $this->getUser();
        $initialPayment = ($this->utils->getUserCreditcardInitialPaymentSuccess($user) === "1") ? false : true;
        $payment->setCredentialsOnFile('CIT', $initialPayment);
        $params = $payment->getRedirectUrlParams();
        if ($params['AccVerify'] !== 'Yes') {
            unset($params['AccVerify']);
        }
        $this->session->offsetSet('fatchipFCSRedirectParams', $params);

        if ($this->config['debuglog'] === 'extended') {
            $sessionID = $this->session->get('sessionId');
            $basket = var_export($this->session->offsetGet('sOrderVariables')->getArrayCopy(), true);
            $customerId = $this->session->offsetGet('sUserId');
            $paymentName = $this->paymentClass;
            $this->utils->log('Redirecting to ' . $payment->getHTTPGetURL($params, $this->config['creditCardTemplate']), ['payment' => $paymentName, 'UserID' => $customerId, 'basket' => $basket, 'SessionID' => $sessionID, 'parmas' => $params]);
        }
        if ($this->config['creditCardMode'] === 'IFRAME') {
            $this->forward('iframe', 'FatchipFCSCreditCard', null, array('fatchipFCSRedirectURL' => $payment->getHTTPGetURL($params, $this->config['creditCardTemplate'])));
        } else if ($this->config['creditCardMode'] === 'PAYMENTPAGE') {
            $this->forward('paymentpage', 'FatchipFCSCreditCard', null, array('fatchipFCSRedirectURL' => $payment->getHTTPGetURL($params, $this->config['creditCardTemplate'])));
        }
    }

    /**
     *  GatewaAction is overridden for Creditcard because:
     *  1. extra param URLBack
     *  2. forward to iframe controller instead of First Cash Solution Gateway, so the First Cash Solution IFrame is shown within Shop layout
     *
     * @return void
     * @throws Exception
     */
    public function browserinfoAction()
    {
        $requestParams = $this->Request()->getParams();
        $this->session->offsetSet('FatchipFCSBrowserInfoParams', $requestParams);

        $this->redirect(['controller' => 'checkout', 'action' => 'confirm']);
    }

    /**
     * Shows First Cash Solution Creditcard Iframe within shop layout
     *
     * @return void
     */
    public function iframeAction()
    {
        $this->view->loadTemplate('frontend/fatchipFCSCreditCard/index.tpl');
        $this->view->assign('fatchipFCSPaymentConfig', $this->config);
        $requestParams = $this->Request()->getParams();
        $this->view->assign('fatchipFCSIframeURL', $requestParams['fatchipFCSRedirectURL']);
        $this->view->assign('fatchipFCSUniqueID', $requestParams['fatchipFCSUniqueID']);
        $this->view->assign('fatchipFCSURL', $requestParams['fatchipFCSURL']);
        $this->view->assign('fatchipFCSErrorMessage', $requestParams['FCSError']['CTErrorMessage']);
        $this->view->assign('fatchipFCSErrorCode', $requestParams['FCSError']['CTErrorCode']);
    }

    /**
     * Shows Computop Creditcard Iframe within shop layout
     *
     * @return void
     */
    public function paymentpageAction()
    {
        $this->view->loadTemplate('frontend/fatchipFCSCreditCard/paymentpage.tpl');
        $this->view->assign('fatchipFCSPaymentConfig', $this->config);
        $requestParams = $this->Request()->getParams();
        $this->view->assign('fatchipFCSIframeURL', $requestParams['fatchipFCSRedirectURL']);
        $this->view->assign('fatchipFCSUniqueID', $requestParams['fatchipFCSUniqueID']);
        $this->view->assign('fatchipFCSURL', $requestParams['fatchipFCSURL']);
        $this->view->assign('fatchipFCSErrorMessage', $requestParams['FCSError']['CTErrorMessage']);
        $this->view->assign('fatchipFCSErrorCode', $requestParams['FCSError']['CTErrorCode']);
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

        // Safari 6+ losses the Shopware session after submitting the iframe, so we restore the session by using
        // the previously sent sessionid returned by CT
        // @see https://gist.github.com/iansltx/18caf551baaa60b79206
        $sessionId = $requestParams['session'];
        if ($sessionId) {
            try {
                $this->restoreSession($sessionId);
            } catch (Zend_Session_Exception $e) {
                $logPath = Shopware()->DocPath();

                if (Util::isShopwareVersionGreaterThanOrEqual('5.1')) {
                    $logFile = $logPath . 'var/log/FatchipFCSPayment_production.log';
                } else {
                    $logFile = $logPath . 'logs/FatchipFCSPayment_production.log';
                }
                $rfh = new RotatingFileHandler($logFile, 14);
                $logger = new \Shopware\Components\Logger('FatchipFCSPayment');
                $logger->pushHandler($rfh);
                $ret = $logger->error($e->getMessage());
            }
        }
        // used for paynow silent mode
        $response = !empty($requestParams['response']) ? $requestParams['response'] : $this->paymentService->getDecryptedResponse($requestParams);

        if ($this->config['debuglog'] === 'extended') {
            $sessionID = $this->session->get('sessionId');
            if (!is_null($this->session->offsetGet('sOrderVariables'))) {
                $basket = var_export($this->session->offsetGet('sOrderVariables')->getArrayCopy(), true);
            } else {
                $basket = 'NULL';
            }
            $customerId = $this->session->offsetGet('sUserId');
            $paymentName = $this->paymentClass;
            $this->utils->log('SuccessAction: ' , ['payment' => $paymentName, 'UserID' => $customerId, 'basket' => $basket, 'SessionID' => $sessionID, 'Request' => $requestParams, 'Response' => $response]);
        }

        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipFCSRedirectParams'), $this->paymentClass, 'AUTH', $response);

        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
            case CTEnumStatus::AUTHORIZED:
            try {
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSRESERVED
                );
            } catch (Exception $e) {
                $this->utils->log('SuccessAction Order could not be saved. Check if session was lost upon returning:' , ['payment' => $paymentName, 'UserID' => $customerId, 'SessionID' => $sessionID, 'response' => $response, 'error' => $e->getMessage()]);
                $this->forward('failure');
            }

            if ($this->config['debuglog'] === 'extended') {
                $sessionID = $this->session->get('sessionId');
                $customerId = $this->session->offsetGet('sUserId');
                $paymentName = $this->paymentClass;
                $this->utils->log('SuccessAction Order was saved with orderNumber : ' . $orderNumber, ['payment' => $paymentName, 'UserID' => $customerId, 'SessionID' => $sessionID]);
            }
                $this->saveTransactionResult($response);

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $result = $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);

                // flag user for successfull initial payment
                $session = Shopware()->Session();
                $this->utils->updateUserCreditcardInitialPaymentSuccess($session->get('sUserId'), true);

                if(!is_null($result) && $result->getStatus() == 'OK') {
                    $this->autoCapture($customOrdernumber);
                }

                $url = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'finish']);

                if ($this->config['creditCardMode'] === 'IFRAME' || $this->config['creditCardMode'] === 'PAYMENTPAGE') {
                    $this->forward('iframe', 'FatchipFCSCreditCard', null, array('fatchipFCSURL' => $url, 'fatchipFCSUniqueID' => $response->getPayID()));
                } else {
                    $this->redirect(array(
                        'controller' => 'checkout',
                        'action' => 'finish',
                        'sUniqueID' => $response->getPayID()
                    ));
                }
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * restore shopware user session from Id
     *
     * @param string $sessionId
     */
    protected function restoreSession($sessionId)
    {
        if (version_compare(Shopware()->Config()->get('version'), '5.7.0', '>=')) {
            Shopware()->Session()->save();
            Shopware()->Session()->setId($sessionId);
            Shopware()->Session()->start();
        } else {
            \Enlight_Components_Session::writeClose();
            \Enlight_Components_Session::setId($sessionId);
            \Enlight_Components_Session::start();
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

        // Safari 6+ losses the Shopware session after submitting the iframe, so we restore the session by using
        // the previously sent sessionid returned by CT
        // @see https://gist.github.com/iansltx/18caf551baaa60b79206
        $sessionId = $requestParams['session'];
        if ($sessionId) {
            try {
                $this->restoreSession($sessionId);
            } catch (Zend_Session_Exception $e) {
            }
        }

        $ctError = [];

        $response = $this->paymentService->getDecryptedResponse($requestParams);

        $this->plugin->logRedirectParams($this->session->offsetGet('fatchipFCSRedirectParams'), $this->paymentClass, 'REDIRECT', $response);

        $ctError['CTErrorMessage'] = Shopware()->Snippets()
            ->getNamespace('frontend/FatchipFCSPayment/translations')
            ->get('errorGeneral'); // . $response->getDescription();
        $ctError['CTErrorCode'] = $response->getCode();
        $ctError = $this->hideError($response->getCode()) ? null : $ctError;
        $url = $this->Front()->Router()->assemble(['controller' => 'checkout', 'action' => 'shippingPayment']);
        $ccMode = strtolower($this->config['creditCardMode']);

        // remove user flag for successfull initial payment
        if (! is_null($this->session->get('sUserId'))) {
            $this->utils->updateUserCreditcardInitialPaymentSuccess($this->session->get('sUserId'), 0);
        }

        if ($ccMode === 'iframe' || $ccMode === 'paymentpage') {
            $this->forward('iframe', 'FatchipFCSCreditCard', null, ['fatchipFCSURL' => $url, 'FCSError' => $ctError]);
        } else {
            //$this->forward('shippingPayment', 'checkout', null, ['FCSError' => $ctError]);
            // set CTError in Session to prevent csfrs errors
            $this->session->offsetSet('FCSError', $ctError);
            $this->redirect(['controller' => 'checkout', 'action' => 'shippingPayment']);
        }
    }

    /**
     * Recurring payment action method.
     */
    public function recurringAction()
    {
        $params = $this->Request()->getParams();
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();

        if ($this->Request()->isXmlHttpRequest()) {

            $payment = $this->getPaymentClassForGatewayAction();
            // check if user already used cc payment successfully and send
            // initialPayment true or false accordingly
            $payment->setCredentialsOnFile('MIT', false);
            $requestParams = $payment->getRedirectUrlParams();
            $requestParams['schemeReferenceID'] = $this->getParamKreditkarteschemereferenceid($params['orderId']);
            /** old 3D Secure 1.0 params */
            unset($requestParams['CCNr']);
            unset($requestParams['CCBrand']);
            unset($requestParams['CCExpiry']);
            unset($requestParams['AccVerify']);

            // $requestParams['credentialOnFile'] = $payment->getCredentialsOnFile();
            $cardParams = [];
            $cardParams['number'] = $this->getParamCCPseudoCardNumber($params['orderId']);;
            $cardParams['brand'] = $this->getParamCCCardBrand($params['orderId']);
            $cardParams['expiryDate'] = $this->getParamCCCardExpiry($params['orderId']);
            $cardParams['cardholderName'] = $this->getParamCCCardholdername($params['orderId']);
            $requestParams['card'] = base64_encode(json_encode($cardParams));

            $response = $this->plugin->callComputopService($requestParams, $payment, 'CreditCardRecurring', $payment->getCTRecurringURL());

            $status = $response->getStatus();
            $allowedStatuses = array(
                CTEnumStatus::OK,
                CTEnumStatus::AUTHORIZED,
            );
            $statusHasErrors = !in_array($status,$allowedStatuses);

            if ($statusHasErrors) {
                $data = [
                    'success' => false,
                    'message' => "Error",
                ];
            } else {
                $orderNumber = $this->saveOrder(
                    $response->getTransID(),
                    $response->getPayID(),
                    self::PAYMENTSTATUSRESERVED
                );
                $this->saveTransactionResultRecurring($response, $cardParams['number'], $cardParams['brand'], $cardParams['expiryDate'], $cardParams['cardholderName']);

                $customOrdernumber = $this->customizeOrdernumber($orderNumber);
                $this->updateRefNrWithComputopFromOrderNumber($customOrdernumber);
                if(!is_null($response) && $response->getStatus() == 'OK') {
                    $this->autoCapture($customOrdernumber, true);
                }
                $data = [
                    'success' => true,
                    'data' => [
                        'orderNumber' => $customOrdernumber,
                        'transactionId' => $response->getTransID(),
                    ],
                ];
            }
            echo Zend_Json::encode($data);
        }
    }

// TODO refactor the 3 methods below because of code duplication

    /**
     * returns creditcard pseudocardnumber from
     * the last order to use it to authorize
     * recurring payments
     *
     * @param string $orderNumber shopware order-number
     *
     * @return boolean | string $pseudoCardNumber pseudo credit card number
     */
    protected function getParamCCPseudoCardNumber($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['id' => $orderNumber]);
        $pseudoCardNumber = false;
        if ($order) {
            $orderAttribute = $order->getAttribute();
            $pseudoCardNumber = $orderAttribute->getfatchipfcsKreditkartepseudonummer();

        }
        return $pseudoCardNumber;
    }

    /**
     * returns creditcard brand from
     * the last order to use it to authorize
     * recurring payments
     *
     * @param string $orderNumber shopware order-number
     *
     * @return boolean | string $cardBrand pseudo credit card number
     */
    protected function getParamCCCardBrand($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['id' => $orderNumber]);
        $cardBrand = false;
        if ($order) {
            $orderAttribute = $order->getAttribute();
            $cardBrand = $orderAttribute->getfatchipfcsKreditkartebrand();

        }
        return $cardBrand;
    }

    /**
     * returns creditcard expiry from
     * the last order to use it to authorize
     * recurring payments
     *
     * @param string $orderNumber shopware order-number
     *
     * @return boolean | string $cardexpiry pseudo credit card number
     */
    protected function getParamCCCardExpiry($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['id' => $orderNumber]);
        $cardexpiry = false;
        if ($order) {
            $orderAttribute = $order->getAttribute();
            $cardexpiry = $orderAttribute->getfatchipfcsKreditkarteexpiry();

        }
        return $cardexpiry;
    }

    /**
     * returns credircard full name from
     * the last order to use it to authorize
     * recurring payments
     *
     * @param string $orderNumber shopware order-number
     *
     * @return boolean | string $cardexpiry pseudo credit card number
     */
    protected function getParamCCCardholdername($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['id' => $orderNumber]);
        $cardholder = false;
        if ($order) {
            $orderAttribute = $order->getAttribute();
            $cardholder = $orderAttribute->getfatchipfcsKreditkartecardholdername();
        }
        return $cardholder;
    }

    /**
     * Saves the TransationIds in the Order attributes.
     * overridden, because the first recurring payment when using AboCommerce
     * will not return a pseudocardnumber or any creditcard attributes which are
     * expected for the next recurring payment
     * So we save the creditcard information of the original order
     *
     * @param \Fatchip\FCSPayment\CTResponse $response Computop Api response
     * @param string $ccNumber
     * @param string $ccBrand
     * @param string $ccExpiry
     * @param string $ccCardHolder
     *
     * @return void
     * @throws Exception
     */
    public function saveTransactionResultRecurring($response, $ccNumber, $ccBrand, $ccExpiry, $ccCardHolder)
    {
        $transactionId = $response->getTransID();
        if ($order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $transactionId])) {
            if ($attribute = $order->getAttribute()) {
                $attribute->setfatchipfcsStatus($response->getStatus());
                $attribute->setfatchipfcsTransid($response->getTransID());
                $attribute->setfatchipfcsPayid($response->getPayID());
                $attribute->setfatchipfcsXid($response->getXID());
                $attribute->setfatchipfcskreditkartepseudonummer($ccNumber);
                $attribute->setfatchipfcskreditkartebrand($ccBrand);
                $attribute->setfatchipfcskreditkarteexpiry($ccExpiry);
                $attribute->setfatchipfcskreditkartecardholdername($ccCardHolder);
                $attribute->setfatchipfcskreditkarteschemereferenceid($response->getSchemeReferenceID());
                $attribute->setfatchipfcsPaypalbillingagreementid($response->getBillingAgreementiD());

                Shopware()->Models()->persist($attribute);
                Shopware()->Models()->flush();
            }
        }
    }
    /**
     * returns schemeReferenceID from
     * the last order to use it to authorize
     * recurring payments
     *
     * @param string $orderNumber shopware order-number
     *
     * @return boolean | string creditcard schemeReferenceID
     */
    protected function getParamKreditkarteschemereferenceid($orderNumber)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['id' => $orderNumber]);
        $refID = false;
        if ($order) {
            $orderAttribute = $order->getAttribute();
            $refID = $orderAttribute->getfatchipfcskreditkarteschemereferenceid();

        }
        return $refID;
    }
}

