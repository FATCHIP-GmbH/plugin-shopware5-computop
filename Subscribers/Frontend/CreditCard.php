<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipFCSPayment
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 First Cash Solution
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.firstcashsolution.de/
 */

namespace Shopware\Plugins\FatchipFCSPayment\Subscribers\Frontend;

use Enlight_Controller_ActionEventArgs;
use Fatchip\FCSPayment\CTPaymentMethodIframe;
use Shopware\Plugins\FatchipFCSPayment\Subscribers\AbstractSubscriber;

class CreditCard extends AbstractSubscriber
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (position defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     * <code>
     * return array(
     *     'eventName0' => 'callback0',
     *     'eventName1' => array('callback1'),
     *     'eventName2' => array('callback2', 10),
     *     'eventName3' => array(
     *         array('callback3_0', 5),
     *         array('callback3_1'),
     *         array('callback3_2')
     *     )
     * );
     *
     * </code>
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onPostDispatchFrontendCheckout',
        ];
    }

    /**
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchFrontendCheckout(Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();
        $pluginConfig = Shopware()->Plugins()->Frontend()->FatchipFCSPayment()->Config()->toArray();

        $session = Shopware()->Session();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);


        if (!$request->isDispatched() or !stristr($paymentName, 'fatchip_firstcash_creditcard')) { // no creditcard payment method
            return;
        }

        if ($request->getActionName() == 'confirm' and $pluginConfig['creditCardMode'] == 'SILENT' and !isset($session->FatchipFCSBrowserInfoParams['javaScriptEnabled'])) {
            // inject javascript template responsible for Browser Detection
            // this will sent all vars via POST Request to this controller
            $router = Shopware()->Front()->Router();
            // $view->assign('url', $router->assemble(['controller' => 'checkout', 'action' => 'confirm', 'forceSecure' => true]));
            $view->assign('url', $router->assemble(['controller' => 'FatchipFCSCreditCard', 'action' => 'browserinfo', 'forceSecure' => true]));
            $view->loadTemplate('frontend/checkout/firstcash_creditcard_confirm_browserdetect.tpl');
        } else if ($request->getActionName() == 'confirm' and $pluginConfig['creditCardMode'] == 'SILENT') {
            $view->assign('fatchipFCSCreditCardMode', "1");

            // the creditcard form send all data directly to payssl.aspx
            // set the neccessary pre-encrypted fields in view
            $payment = $this->getPaymentClassForGatewayAction();
            $payment->setCapture('MANUAL');

            $shopContext = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
            $shopName = $shopContext->getShop()->getName();
            $payment->setOrderDesc($shopName);

            // check if user already used cc payment successfully and send
            // initialPayment true or false accordingly
            $initialPayment = ($this->utils->getUserCreditcardInitialPaymentSuccess($userData) === "1") ? false : true;
            $payment->setCredentialsOnFile('CIT', $initialPayment);

            $requestParams = $payment->getRedirectUrlParams();
            if ($requestParams['AccVerify'] !== 'Yes') {
                unset($requestParams['AccVerify']);
            }
            $requestParams['browserInfo'] = $this->getParamBrowserInfo($session->FatchipFCSBrowserInfoParams, $request);
            unset($requestParams['Template']);
            $silentParams = $payment->prepareSilentRequest($requestParams);
            $session->offsetSet('fatchipFCSRedirectParams', $requestParams);

            $view->assign('creditCardSilentModeBrandsVisa', (int)$pluginConfig['creditCardSilentModeBrandsVisa']);
            $view->assign('creditCardSilentModeBrandsMaster', (int)$pluginConfig['creditCardSilentModeBrandsMaster']);
            $view->assign('creditCardSilentModeBrandsAmex', (int)$pluginConfig['creditCardSilentModeBrandsAmex']);

            $view->assign('fatchipFCSCreditCardSilentParams', $silentParams);
            $view->extendsTemplate('frontend/checkout/firstcash_creditcard_confirm.tpl');
        }
    }

    /** Duplicate methods from payment controller
     * to set pre-encrypted data into shippingpayment view
     * Helper function that creates a payment object
     * @return CTPaymentMethodIframe
     */
    protected function getPaymentClassForGatewayAction()
    {
        $paymentService = Shopware()->Container()->get('FatchipFCSPaymentApiClient');

        $ctOrder = $this->utils->createCTOrder();
        $router = Shopware()->Front()->Router();
        $payment = $paymentService->getIframePaymentClass(
            'CreditCard',
            $this->config,
            $ctOrder,
            $router->assemble(['controller' => 'FatchipFCSCreditCard', 'action' => 'success', 'forceSecure' => true]),
            $router->assemble(['controller' => 'FatchipFCSCreditCard', 'action' => 'failure', 'forceSecure' => true]),
            $router->assemble(['controller' => 'FatchipFCSCreditCard', 'action' => 'notify', 'forceSecure' => true]),
            null,
            $this->utils->getUserDataParam()
        );

        return $payment;
    }

    protected function getParamBrowserInfo($browserData, $request)
    {
        // @see
        $acceptHeaders = $request->getHeader('accept');
        $ipAddress = $request->getClientIp();
        $javaEnabled = $browserData['javaEnabled'];
        $javaScriptEnabled = $browserData['javaScriptEnabled']; // see above
        $acceptLang = $request->getHeader('accept-language');
        $language = array_shift(explode(',', $acceptLang));
        $colorDepth = $this->getParamColorDepth((int)$browserData['colorDepth']);
        $screenHeight = $browserData['screenHeight'];
        $screenWidth = $browserData['screenWidth'];
        $timeZoneOffset = $browserData['timeZoneOffset'];
        $userAgent = $request->getHeader('user-agent');

        if ($browserData['javaScriptEnabled'] === "false") {
            $browserInfoParams = array(
                'acceptHeaders' => $acceptHeaders,
                'ipAddress' => $ipAddress,
                'javaScriptEnabled' => ($javaScriptEnabled === "true") ? true : false,
                'language' => $language,
                'userAgent' => $userAgent,
            );
        } else {
            $browserInfoParams = array(
                'acceptHeaders' => $acceptHeaders,
                'ipAddress' => $ipAddress,
                'javaEnabled' => ($javaEnabled === "true") ? true : false,
                'javaScriptEnabled' => ($javaScriptEnabled === "true") ? true : false,
                'language' => $language,
                'colorDepth' => (int)$colorDepth,
                'screenHeight' => (int)$screenHeight,
                'screenWidth' => (int)$screenWidth,
                'timeZoneOffset' => $timeZoneOffset,
                'userAgent' => $userAgent,
            );
        }
        return base64_encode(json_encode($browserInfoParams));
    }

    /**
     * The computop API only accepts the values
     * 1,4,8,15,16,24,32 and 48
     * This method returns the next higher fitting value
     * when there is no exect match
     *
     * @param int $colorDepth
     * @return int $apiColorDepth
     */
    private function getParamColorDepth($colorDepth)
    {
        $acceptedValues = [1, 4, 8, 15, 16, 24, 32, 48];

        if (in_array($colorDepth, $acceptedValues, true)) {
            $apiColorDepth = $colorDepth;
        } elseif ($colorDepth <=1 ) {
            $apiColorDepth = 1;
        } elseif ($colorDepth <= 4) {
            $apiColorDepth = 4;
        } elseif ($colorDepth <= 8) {
            $apiColorDepth = 8;
        } elseif ($colorDepth <= 15) {
            $apiColorDepth = 15;
        } elseif ($colorDepth <= 24){
            $apiColorDepth = 24;
        } elseif ($colorDepth <= 32){
            $apiColorDepth = 32;
        } else {
            $apiColorDepth = 48;
        }
        return $apiColorDepth;
    }
}
