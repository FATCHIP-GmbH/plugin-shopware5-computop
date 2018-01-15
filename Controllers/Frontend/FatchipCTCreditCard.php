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

use Fatchip\CTPayment\CTPaymentMethodsIframe\CreditCard;
use Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseCreditCard;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTEnums\CTEnumStatus;
use Shopware\FatchipCTPayment\Util;

/**
 * Class Shopware_Controllers_Frontend_FatchipCTCreditCard
 */
class Shopware_Controllers_Frontend_FatchipCTCreditCard extends Shopware_Controllers_Frontend_Payment
{

    /**
     * @return void
     * @throws Exception
     */
    public function gatewayAction()
    {
        $router = $this->Front()->Router();
        $user = $this->getUser();
        $util = new Util();

        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $config = $plugin->Config()->toArray();
        // ToDo: handle possible exception here
        $service = $this->container->get('FatchipCTPaymentApiClient');

        // ToDo refactor ctOrder creation
        $ctOrder = new CTOrder();
        $ctOrder->setAmount($this->getAmount());
        $ctOrder->setCurrency($this->getCurrencyShortName());
        $ctOrder->setBillingAddress($util->getCTAddress($user['billingaddress']));
        $ctOrder->setShippingAddress($util->getCTAddress($user['shippingaddress']));

        // ToDo should this be done in the CTPaymentService?
        $myCC = new CreditCard(
            $config,
            $ctOrder,
            $router->assemble(['action' => 'success', 'forceSecure' => false]),
            $router->assemble(['action' => 'failure', 'forceSecure' => false]),
            $router->assemble(['action' => 'notify', 'forceSecure' => false])
        );
        $myCC->setUserData($service->createPaymentToken($this->getAmount(), $user['billing']['customernumber']));
        $this->redirect($myCC->getHTTPGetURL());

    }

    /**
     * Cancel action method
     * @return void
     * @throws Exception
     */
    public function failureAction()
    {
        $requestParams = $this->Request()->getParams();

        /** @var \Fatchip\CTPayment\CTPaymentService $service */
        $service = $this->container->get('FatchipCTPaymentApiClient');

        $response = $service->createPaymentResponse($requestParams);
        // ToDo extend shippingPayment template to show errors instead of dying ;)
        return $this->redirect(['controller' => 'checkout', 'action' => 'shippingPayment', 'sTarget' => 'checkout']);
    }

    /**
     * success action method
     * @return void
     * @throws Exception
     */
    public function successAction()
    {
        $requestParams = $this->Request()->getParams();
        $user = $this->getUser();

        /** @var \Fatchip\CTPayment\CTPaymentService $service */
        $service = $this->container->get('FatchipCTPaymentApiClient');

        /** @var CTResponseCreditCard $response */
        $response = $service->createPaymentResponse($requestParams);
        $token = $service->createPaymentToken($this->getAmount(), $user['billingaddress']['customernumber']);

        if (!$service->isValidToken($response, $token)) {
            $this->forward('failure');
            return;
        }
        switch ($response->getStatus()) {
            case CTEnumStatus::OK:
                $this->saveOrder(
                    $response->getTransID(),
                    $response->getUserData(),
                    self::PAYMENTSTATUSPAID
                );
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                break;
            default:
                $this->forward('failure');
                break;
        }
    }

    /**
     * notify action method
     * @return void
     * @throws Exception
     */
    public function notifyAction()
    {
    }
}
