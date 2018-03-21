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
 * @subpackage Bootstrap
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Shopware\Models\Payment\RuleSet;
use Shopware\Models\Payment\Payment;

/**
 * Class RiskRules
 *
 * creates risk rules for payment methods.
 */
class RiskRules
{
    /**
     * FatchipCTpayment Plugin Bootstrap Class
     * @var \Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap
     */
    private $plugin;

    /**
     * RiskRules constructor.
     */
    public function __construct()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
    }

    /**
     * Create risk rules.
     *
     * @see createComputopRiskRule
     *
     * @throws \Exception
     * @return void
     */
    public function createRiskRules()
    {
        $this->createComputopRiskRule('fatchip_computop_easycredit',
            'ORDERVALUELESS', '200');

        $this->createComputopRiskRule('fatchip_computop_przelewy24',
            'CURRENCIESISOISNOT', 'PLN');

        $this->createComputopRiskRule('fatchip_computop_ideal',
            'BILLINGLANDISNOT', 'NL');

        $this->createComputopRiskRule('fatchip_computop_klarna_invoice',
            'BILLINGLANDIS', 'SE', 'CURRENCIESISOISNOT', 'SEK');

        $this->createComputopRiskRule('fatchip_computop_klarna_invoice',
            'BILLINGLANDIS', 'NO', 'CURRENCIESISOISNOT', 'NOK');

        $this->createComputopRiskRule('fatchip_computop_klarna_invoice',
            'BILLINGLANDIS', 'DK', 'CURRENCIESISOISNOT', 'DKK');

        $this->createComputopRiskRule('fatchip_computop_klarna_installment',
            'BILLINGLANDIS', 'SE', 'CURRENCIESISOISNOT', 'SEK');

        $this->createComputopRiskRule('fatchip_computop_klarna_installment',
            'BILLINGLANDIS', 'NO', 'CURRENCIESISOISNOT', 'NOK');

        $this->createComputopRiskRule('fatchip_computop_klarna_installment',
            'BILLINGLANDIS', 'DK', 'CURRENCIESISOISNOT', 'DKK');
    }


    /**
     * Create risk rules.
     *
     * @see RuleSet
     *
     * @param string $paymentName payment method to restrict
     * @param string $rule1
     * @param string $value1
     * @param string $rule2
     * @param string $value2
     *
     * @throws \Exception
     * @return void
     */
    private function createComputopRiskRule($paymentName, $rule1, $value1, $rule2 = '', $value2 = '')
    {
        /** @var \Shopware\Components\Model\ModelManager $manager */
        $manager = $this->plugin->get('models');
        $payment = $this->getPaymentObjByName($paymentName);

        // ToDo refactor rules array in case we have more rules for other payments
        $rules = [];
        $valueRule = new RuleSet();
        $valueRule->setRule1($rule1);
        $valueRule->setValue1($value1);
        $valueRule->setRule2($rule2);
        $valueRule->setValue2($value2);
        $valueRule->setPayment($payment);
        $rules[] = $valueRule;

        if ($payment->getRuleSets() === null ||
            $payment->getRuleSets()->count() < $this->getNumberOfRiskrules($paymentName)) {
            $payment->setRuleSets($rules);
            foreach ($rules as $rule) {
                $manager->persist($rule);
            }
            $manager->flush($payment);
        }
    }

    /**
     * return the number of risk rules for payments.
     *
     * this return the EXPECTED numebr of risk rules for klarna payments
     * this is used to avoid overwriting pre-existing rules on updates
     *
     * @param string $paymentName payment method name
     *
     * @return integer
     */
    function getNumberOfRiskrules($paymentName)
    {
        if ($paymentName == 'fatchip_computop_klarna_installment' || $paymentName == 'fatchip_computop_klarna_invoice') {
            return 3;
        }
        return 1;
    }

    /**
     * return payment object by payment name.
     *
     * @param string $paymentName payment method name
     *
     * @return Payment|null
     */
    private function getPaymentObjByName($paymentName)
    {
        /** @var Payment $result */
        $result = $this->plugin->Payments()->findOneBy(
            [
                'name' => [
                    $paymentName,
                ]
            ]
        );
        return $result;
    }
}
