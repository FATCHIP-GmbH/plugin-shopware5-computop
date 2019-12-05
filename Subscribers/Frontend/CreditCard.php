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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Subscibers
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Subscribers\Frontend;

use Enlight_Controller_ActionEventArgs;
use Fatchip\CTPayment\CTPaymentMethodIframe;
use Shopware\Plugins\FatchipCTPayment\Subscribers\AbstractSubscriber;

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
        $pluginConfig = Shopware()->Plugins()->Frontend()->FatchipCTPayment()->Config()->toArray();

        $session = Shopware()->Session();

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $paymentName = $this->utils->getPaymentNameFromId($userData['additional']['payment']['id']);

        if ($request->getActionName() == 'confirm' && $paymentName == 'fatchip_computop_creditcard' && $pluginConfig['creditCardMode'] == 'SILENT') {

            $view->assign('fatchipCTCreditCardMode', "1");
            // the creditcard form send all data directly to payssl.aspx
            // set the neccessary pre-encrypted fields in view
            $payment = $this->getPaymentClassForGatewayAction();
            $payment->setCapture('MANUAL');

            $shopContext = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
            $shopName = $shopContext->getShop()->getName();
            $payment->setOrderDesc($shopName);

            $requestParams = $payment->getRedirectUrlParams();
            unset($requestParams['Template']);
            $silentParams = $payment->prepareSilentRequest($requestParams);
            $session->offsetSet('fatchipCTRedirectParams', $requestParams);


            $view->assign('creditCardSilentModeBrandsVisa', (int)$pluginConfig['creditCardSilentModeBrandsVisa']);
            $view->assign('creditCardSilentModeBrandsMaster', (int)$pluginConfig['creditCardSilentModeBrandsMaster']);
            $view->assign('creditCardSilentModeBrandsAmex', (int)$pluginConfig['creditCardSilentModeBrandsAmex']);

            $view->assign('fatchipCTCreditCardSilentParams', $silentParams);
            $view->extendsTemplate('frontend/checkout/creditcard_confirm.tpl');
        }
    }

    /** Duplicate methods from payment controller
     * to set pre-encrypted data into shippingpayment view
     * Helper function that creates a payment object
     * @return CTPaymentMethodIframe
     */
    protected function getPaymentClassForGatewayAction()
    {
        $paymentService = Shopware()->Container()->get('FatchipCTPaymentApiClient');

        $ctOrder = $this->utils->createCTOrder();
        $router = Shopware()->Front()->Router();
        $payment = $paymentService->getIframePaymentClass(
            'CreditCard',
            $this->config,
            $ctOrder,
            $router->assemble(['controller' => 'FatchipCTCreditCard', 'action' => 'success', 'forceSecure' => true]),
            $router->assemble(['controller' => 'FatchipCTCreditCard', 'action' => 'failure', 'forceSecure' => true]),
            $router->assemble(['controller' => 'FatchipCTCreditCard', 'action' => 'notify', 'forceSecure' => true]),
            null,
            $this->utils->getUserDataParam()
        );

        return $payment;
    }
}
