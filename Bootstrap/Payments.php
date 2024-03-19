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
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Bootstrap
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Doctrine\ORM\ORMException;
use Fatchip\CTPayment\CTPaymentService;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Models\Country\Country;
use Shopware\Models\Payment\Payment;

/**
 * Class Payments.
 *
 * creates payment methods.
 */
class Payments extends Bootstrap
{
    /**
     * Create payment methods.
     *
     * @return void
     * @throws ORMException
     * @see CTPaymentService::getPaymentMethods()
     * @see \Shopware_Components_Plugin_Bootstrap::createPayment()
     *
     */
    public function createPayments()
    {
        /** @var CTPaymentService $service */
        $service = new CTPaymentService(null);
        $paymentMethods = $service->getPaymentMethods();

        $this->updatePaydirekt();

        foreach ($paymentMethods as $paymentMethod) {
            if ($this->plugin->Payments()->findOneBy(array('name' => $paymentMethod['name']))) {
                if ($paymentMethod['name'] === 'fatchip_computop_afterpay_invoice' ||
                    $paymentMethod['name'] === 'fatchip_computop_afterpay_installment'
                ) {
                    $this->updateAfterpay($paymentMethod);
                }
                continue;
            }

            $payment = [
                'name' => $paymentMethod['name'],
                'description' => $paymentMethod['description'],
                'action' => $paymentMethod['action'],
                'active' => 0,
                'template' => $paymentMethod['template'],
                'additionalDescription' => $paymentMethod['additionalDescription'],
            ];

            $paymentObject = $this->plugin->createPayment($payment);

            if (!empty($paymentMethod['countries'])) {
                $this->restrictPaymentShippingCountries($paymentObject, $paymentMethod['countries']);
            }
        }
    }

    /** make sure afterpay template names are set correctly
     * needed for upgrading form 1.0.12 / 1.0.13 to 1.0.14
     * @param $paymentMethod
     * @return void
     * @throws ORMException
     */
    protected function updateAfterpay($paymentMethod)
    {
        $payment = $this->plugin->Payments()->findOneBy(array('name' => $paymentMethod['name']));
        // update payment template
        if ($paymentMethod['name'] === 'fatchip_computop_afterpay_installment') {
            $payment->setTemplate('fatchip_computop_afterpay_installment.tpl');
        }
        if ($paymentMethod['name'] === 'fatchip_computop_afterpay_invoice') {
            $payment->setTemplate('fatchip_computop_afterpay_invoice.tpl');
        }
        Shopware()->Models()->persist($payment);
        Shopware()->Models()->flush($payment);
    }

    /** make sure afterpay template names are set correctly
     * needed for upgrading form 1.0.12 / 1.0.13 to 1.0.14
     * @param $paymentMethod
     * @return void
     * @throws ORMException
     */
    protected function updatePaydirekt()
    {
        $payment = $this->plugin->Payments()->findOneBy(array('name' => 'fatchip_computop_paydirekt'));
        // update payment template
        if ($payment) {
            $payment->setName('fatchip_computop_giropay');
            $payment->setDescription('Computop Giropay');
            Shopware()->Models()->persist($payment);
            Shopware()->Models()->flush($payment);
        }
    }

    /**
     * Restrict payment method to countries.
     *
     *
     * @see \Shopware\Models\Payment\Payment::setCountries()
     *
     * @param Payment $paymentObject payment method to restrict
     * @param ArrayCollection $countries countries to restrict
     *
     * @return void
     */
    private function restrictPaymentShippingCountries($paymentObject, $countries)
    {
        $countryCollection = new ArrayCollection();
        foreach ($countries as $countryIso) {
            $country =
                Shopware()->Models()->getRepository(Country::class)->findOneBy(['iso' => $countryIso]);
            if ($country !== null) {
                $countryCollection->add($country);
            }
        }
        $paymentObject->setCountries($countryCollection);
    }
}
