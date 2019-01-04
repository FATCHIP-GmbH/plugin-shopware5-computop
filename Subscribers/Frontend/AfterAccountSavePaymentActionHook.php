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


namespace Shopware\Plugins\FatchipCTPayment\Subscribers\Frontend;

use Enlight_Hook_HookArgs;

use Shopware\Plugins\FatchipCTPayment\Subscribers\AbstractSubscribers\AbstractAccountSubscriber;

class AfterAccountSavePaymentActionHook extends AbstractAccountSubscriber
{
    /**
     * return array with all subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Account::savePaymentAction::after' => 'account__savePaymentAction__after',
        ];
    }

    /**
     * assign saved payment data to view
     *
     * @param Enlight_Hook_HookArgs $arguments
     */
    public function account__savePaymentAction__after(Enlight_Hook_HookArgs $arguments)
    {
        $subject = $arguments->getSubject();
        $params = $subject->Request()->getParams()['FatchipComputopPaymentData'];
        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $pluginConfig = Shopware()->Container()->get('plugins')->Frontend()->FatchipCTPayment()->Config()->toArray();

        if ( ! empty($params)) {
            $this->utils->updateUserLastschriftBank(
                $userData['additional']['user']['userID'],
                $params['fatchip_computop_lastschrift_bank']
            );

            $this->utils->updateUserLastschriftKontoinhaber(
                $userData['additional']['user']['userID'],
                $params['fatchip_computop_lastschrift_kontoinhaber']
            );

            $XXXX_iban = false !== strpos($params['fatchip_computop_lastschrift_iban_anon'], '#XXXX#');

            $isIbanAnon = $pluginConfig['lastschriftAnon'] !== 'Aus';
            if ( ! $isIbanAnon) {
                $this->utils->updateUserLastschriftIban(
                    $userData['additional']['user']['userID'],
                    $params['fatchip_computop_lastschrift_iban']
                );
            } elseif ($XXXX_iban) {
                $this->utils->updateUserLastschriftIban(
                    $userData['additional']['user']['userID'],
                    $params['fatchip_computop_lastschrift_iban']
                );
            } else {
                $this->utils->updateUserLastschriftIban(
                    $userData['additional']['user']['userID'],
                    $params['fatchip_computop_lastschrift_iban_anon']
                );
            }
        }
    }
}
