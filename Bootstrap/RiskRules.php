<?php

namespace Shopware\Plugins\FatchipCTPayment\Bootstrap;

use Shopware\Models\Payment\RuleSet;
use Shopware\Models\Payment\Payment;

class RiskRules
{
    /***
     *  Creates the settings page for this plugin.
     */

    private $plugin;

    public function __construct()
    {
        $this->plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
    }

    /**
     * create risk rules
     */
    public function createRiskRules()
    {
        $this->createComputopRiskRule('fatchip_computop_easycredit',
            'ORDERVALUELESS', '200');

        $this->createComputopRiskRule('fatchip_computop_przelewy24',
            'CURRENCIESISOISNOT', 'PLN');

        $this->createComputopRiskRule('fatchip_computop_ideal',
            'BILLINGLANDISNOT', 'NL');
    }

    private function createComputopRiskRule($paymentName, $rule1, $value1)
    {
        /** @var \Shopware\Components\Model\ModelManager $manager */
        $manager = $this->plugin->get('models');
        $payment = $this->getPaymentObjByName($paymentName);

        // ToDo refactor rules array in case we have more rules for other payments
        $rules = [];
        $valueRule = new RuleSet();
        $valueRule->setRule1($rule1);
        $valueRule->setValue1($value1);
        $valueRule->setRule2('');
        $valueRule->setValue2('');
        $valueRule->setPayment($payment);
        $rules[] = $valueRule;

        // only add risk rules if no rules are set

        if ($payment->getRuleSets() === null ||
            $payment->getRuleSets()->count() === 0) {
            $payment->setRuleSets($rules);
            foreach ($rules as $rule) {
                $manager->persist($rule);
            }
            $manager->flush($payment);
        }
    }

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
