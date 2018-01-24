<?php

namespace  Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Shopware\Components\DependencyInjection\Container;
use Shopware\FatchipCTPayment\Util;

/**
 * Class AddressCheck
 *
 * @package Shopware\Plugins\MoptPaymentPayone\Subscribers
 */
class FrontendRiskManagement implements SubscriberInterface
{

    /**
    * di container
    *
    * @var Container
    */
    private $container;
    
    /**
     * inject di container
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // risk management:Frontend extend sAdmin prepare CT risk checks
                'sAdmin::sManageRisks::before' => 'sAdmin__sManageRisks__before',
            // risk management:Frontend extend sAdmin clean up payone risk checks
            'sAdmin::sManageRisks::after' => 'sAdmin__sManageRisks__after',
            // risk management:Frontend extend sAdmin - check CT risks
            'sAdmin::executeRiskRule::replace' => 'sAdmin__executeRiskRule',
            // hook for saving addresscheck result during registration process
            'Shopware_Controllers_Frontend_Register::saveRegisterAction::after' => 'onSaveRegister',
            // hook for invalidating a changed address in Shopware versions > 5.2
            'Shopware_Controllers_Frontend_Address::ajaxSaveAction::after' => 'onUpdateAddress',
            // hook for saving shipmentaddresscheck result
            'sAdmin::sUpdateShipping::after' => 'onUpdateShipping',
            // check if consumerscore is valid if activated
            'Shopware_Controllers_Frontend_Checkout::shippingPaymentAction::after' => 'onShippingPaymentAction',
        ];
    }
    
  /**
   * prepare Computop risk checks
   *
   * @param \Enlight_Hook_HookArgs $arguments
   * @return boolean
   */
    public function sAdmin__sManageRisks__before(\Enlight_Hook_HookArgs $arguments)
    {
        Shopware()->Session()->CTRiskCheckPaymentId = $arguments->get('paymentID');
    }
  
  /**
   * clean up Computop risk checks
   *
   * @param \Enlight_Hook_HookArgs $arguments
   * @return boolean
   */
    public function sAdmin__sManageRisks__after(\Enlight_Hook_HookArgs $arguments)
    {
        unset(Shopware()->Session()->CTRiskCheckPaymentId);
    }
  
    /**
     * handle rules beginning with 'sRiskMOPT_PAYONE__'
     * returns true if risk condition is fulfilled
     * arguments: $rule, $user, $basket, $value
     *
     * * @param \Enlight_Hook_HookArgs $arguments
     */
    public function sAdmin__executeRiskRule(\Enlight_Hook_HookArgs $arguments)
    {
        $rule = $arguments->get('rule');

        // execute parent call if rule is not payone
        if (strpos($rule, 'sRiskFATCHIP_COMPUTOP__') !== 0) {
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                )
            );
        } else {

            /** @var \Fatchip\CTPayment\CTPaymentService $service */
            $service = Shopware()->Container()->get('FatchipCTPaymentApiClient');
            $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
            $config = $plugin->Config()->toArray();
            //only execute riskcheck if a CRIF method is set in config.
            if (!isset($config['crifmethod']) || $config['crifmethod'] == 'inactive' ) {
                $arguments->setReturn(false);
                return;
            }

            $user = Shopware()->Session()->sOrderVariables['sUserData'];
            $util = new Util();

            //$value contains the value that we want to compare with, as set in the SW Riskmanagment Backend Rule
            $value = $arguments->get('value');
            $basket = $arguments->get('basket');
            $user = $arguments->get('user');

            $userId = $user['additional']['user']['id'] ? $user['additional']['user']['id'] : null;
            $userObject = $userId ? Shopware()->Models()
              ->getRepository('Shopware\Models\Customer\Customer')
              ->find($userId) : null;

            //If we don't have a userobject yet, there is no point in doing a risk check
            if (!$userObject){
                $arguments->setReturn(false);
                return;
            }

            $billingAddressData = $user['billingaddress'];
            $billingAddressData['country']  = $billingAddressData['countryId'];
            $shippingAddressData = $user['shippingaddress'];
            $shippingAddressData['country'] = $billingAddressData['countryId'];

            $ctOrder = new CTOrder();
            $ctOrder->setAmount($basket['AmountNumeric'] * 100);
            $ctOrder->setCurrency('EUR'); //TODO: auslesen
            $ctOrder->setBillingAddress($util->getCTAddress($user['billingaddress']));
            $ctOrder->setShippingAddress($util->getCTAddress($user['shippingaddress']));
            $ctOrder->setEmail($user['additional']['user']['email']);

            //only make a call to the CRIF service if Necessary
            if ($this->newCRIFCheckIsNecessary($userId)) {

                //TODO: Set orderDesc and Userdata
                $crif = $service->getCRIFClass($config, $ctOrder, 'testOrder', 'testUserData');
                //make the call to CRIF
                $rawResp = $crif->callCRFDirect();
                /** @var \Fatchip\CTPayment\CTResponse\CTResponseIframe\CTResponseCRIF $crifResponse*/
                $crifResponse = $service->createCRIFResponse($rawResp);
                $status = $crifResponse->getStatus();
                $callResult = $crifResponse->getResult();
                //write the result to the session for this billingaddressID
                $crifInformation[$billingAddressData['id']] =  $this->getCRIFResponseArray($crifResponse);
                //and save the result
                $util->saveCRIFResult('billing', $userId, $crifResponse);
                $util->saveCRIFResult('shipping', $userId, $crifResponse);
            } else {
                $crifBillingResultFromDB = $util->getBillingCRIFResultFromDB($userId);
                $callResult = $crifBillingResultFromDB['FatchipcComputopCrifResult'];
            }

            if ($this->$rule($callResult, $value)) {
                $arguments->setReturn(true);
                return;
            }

        }
    }

    private function getCRIFResponseArray($crifResponseObject) {
        $crifResponseArray = array();
        $crifResponseArray['Code'] = $crifResponseObject->getCode();
        $crifResponseArray['Description'] = $crifResponseObject->getDescription();
        $crifResponseArray['result'] = $crifResponseObject->getResult();
        $crifResponseArray['status'] = $crifResponseObject->getStatus();
        return $crifResponseArray;
    }


    /**
     * Checks in the Billing and Shipping attributes if a CRIF Check have already been made in the past.
     * If yes, it is checked if it is older then the number of days after which saved CRIF results should be invalidated
     * (set in the plugin-setttings)
     *
     * Returns true if we have to make the CRIF call to Computop, or false if we can use the saved values.
     *
     * @param $userID
     * @return bool
     */
    private function newCRIFCheckIsNecessary($userID) {
        $util = new Util();
        $crifBillingResultFromDB = $util->getBillingCRIFResultFromDB($userID);
        $crifShippingResultFromDB = $util->getShippingCRIFResultFromDB($userID);
        // if no CRIF data is saved in Billing ord Shippingaddress, return true
        if (!isset($crifBillingResultFromDB['FatchipcComputopCrifResult'])
            || !isset($crifBillingResultFromDB['FatchipcComputopCrifDate'])
            || !isset($crifShippingResultFromDB['FatchipcComputopCrifResult'])
            || !isset($crifBillingResultFromDB['FatchipcComputopCrifDate'])
        ) {
            return true;
        }
        //if CRIF data IS saved in both addresses, check if the are expired,
        //that means, they are older then the number of days set in Pluginsettings
        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $config = $plugin->Config()->toArray();
        $invalidateAfterDays = $config['bonitaetinvalidateafterdays'];
        if (is_numeric($invalidateAfterDays) && $invalidateAfterDays > 0) {
            /** @var \DateTime $lastTimeBillingChecked */
            $lastTimeBillingChecked =  $crifBillingResultFromDB['FatchipcComputopCrifDate'];
            $daysPassedBilling = $lastTimeBillingChecked->diff(new \DateTime('now'), true)->days;
            $lastTimeShippingChecked =  $crifShippingResultFromDB['FatchipcComputopCrifDate'];
            $daysPassedShipping = $lastTimeShippingChecked->diff(new \DateTime('now'), true)->days;
            if ($daysPassedBilling > $invalidateAfterDays || $daysPassedShipping > $invalidateAfterDays) {
                return true;
            }
        }

        return false;
    }

    /**
     * invalidate all check results on address change
     *
     * @param \Enlight_Hook_HookArgs $arguments
     */
    public function onUpdateAddress(\Enlight_Hook_HookArgs $arguments)
    {

        //TODO: CHECK WHY THIS DOESNT WORK! Test: Click Change Address. After that newCRIFCheckIsNecessary still returns false,
        //should be true
        try {
            $userId = Shopware()->Session()->sUserId;
            $util = new Util();
            $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);
            $userAttribute = $util->getOrCreateUserAttribute($user);
            $userAttribute->setFatchipcComputopCrifDate(0);
            Shopware()->Models()->persist($userAttribute);
            Shopware()->Models()->flush();
            $billing = $user->getBilling();
            $billingAttribute = $util->getOrCreateBillingAttribute($billing);
            $billingAttribute->setFatchipcComputopCrifDate(0);
            Shopware()->Models()->persist($billingAttribute);
            Shopware()->Models()->flush();
            $shipping = $user->getShipping();
            $shippingAttribute = $util->getOrCreateShippingAttribute($shipping);
            $shippingAttribute->setFatchipcComputopCrifDate(0);
            Shopware()->Models()->persist($shippingAttribute);
            Shopware()->Models()->flush();
        } catch (\Exception $exception) {
            unset($exception); // Ignore errors
        }
    }




    /**
     * check if user score equals configured score to block payment method
     *
     * @param $scoring
     * @param $value
     * @return bool
     */
    public function sRiskFATCHIP_COMPUTOP__TRAFFIC_LIGHT_IS($scoring, $value)
    {
        return $scoring == $value; //return true if payment has to be denied
    }


    /**
     * check if user score equals not configured score to block payment method
     *
     * @param $scoring
     * @param $value
     * @return bool
     */
    public function sRiskFATCHIP_COMPUTOP__TRAFFIC_LIGHT_IS_NOT($scoring, $value)
    {
        return !$this->sRiskFATCHIP_COMPUTOP__TRAFFIC_LIGHT_IS($scoring, $value);
    }


    /**
     * save addresscheck result
     *
     * @param \Enlight_Hook_HookArgs $arguments
     */
    public function onSaveRegister(\Enlight_Hook_HookArgs $arguments)
    {
        $this->onUpdateBilling($arguments);
        $this->onUpdateShipping($arguments);
    }


    /**
     * save addresscheck result
     *
     * @param \Enlight_Hook_HookArgs $arguments
     */
    public function onUpdateBilling(\Enlight_Hook_HookArgs $arguments)
    {
        $session = Shopware()->Session();

        if (!($result = unserialize($session->moptPayoneBillingAddresscheckResult))) {
            return;
        }

        $userId         = $session->sUserId;
        $moptPayoneMain = $this->container->get('MoptPayoneMain');
        $config         = $moptPayoneMain->getPayoneConfig();

        if ($result->getStatus() === \Payone_Api_Enum_ResponseType::INVALID ||
            $result->getStatus() === \Payone_Api_Enum_ResponseType::ERROR
        ) {
            $moptPayoneMain->getHelper()->saveBillingAddressError($userId, $result);
        } else {
            if ($result->getStatus() === \Payone_Api_Enum_ResponseType::VALID &&
                $result->getSecstatus() === '20' &&
                $config['adresscheckAutomaticCorrection'] === 0 &&
                Shopware()->Modules()->Admin()->sSYSTEM->_GET['action'] === 'saveRegister'
            ) {
                $moptPayoneMain->getHelper()->saveCorrectedBillingAddress($userId, $result);
            }
            $mappedPersonStatus = $moptPayoneMain->getHelper()
                ->getUserScoringValue($result->getPersonstatus(), $config);
            $mappedPersonStatus = $moptPayoneMain->getHelper()
                ->getUserScoringColorFromValue($mappedPersonStatus);
            $moptPayoneMain->getHelper()
                ->saveAddressCheckResult('billing', $userId, $result, $mappedPersonStatus);
        }

        unset($session->moptPayoneBillingAddresscheckResult);
    }

    /**
     * save addresscheck result
     *
     * @param \Enlight_Hook_HookArgs $arguments
     */
    public function onUpdateShipping(\Enlight_Hook_HookArgs $arguments)
    {
        //TODO: Check if this is needed
        return;
        $session = Shopware()->Session();

        if (!($result = unserialize($session->moptPayoneShippingAddresscheckResult))) {
            return;
        }

        $userId         = $session->sUserId;
        $moptPayoneMain = $this->container->get('MoptPayoneMain');
        $config         = $moptPayoneMain->getPayoneConfig();

        if ($result->getStatus() === \Payone_Api_Enum_ResponseType::INVALID ||
            $result->getStatus() === \Payone_Api_Enum_ResponseType::ERROR
        ) {
            $moptPayoneMain->getHelper()->saveShippingAddressError($userId, $result);
        } else {
            if ($result->getStatus() === \Payone_Api_Enum_ResponseType::VALID &&
                $result->getSecstatus() === '20' &&
                $config['adresscheckAutomaticCorrection'] === 0 &&
                Shopware()->Modules()->Admin()->sSYSTEM->_GET['action'] === 'saveRegister'
            ) {
                $moptPayoneMain->getHelper()->saveCorrectedShippingAddress($userId, $result);
            }

            $mappedPersonStatus = $moptPayoneMain->getHelper()
                ->getUserScoringValue($result->getPersonstatus(), $config);
            $mappedPersonStatus = $moptPayoneMain->getHelper()
                ->getUserScoringColorFromValue($mappedPersonStatus);
            $moptPayoneMain->getHelper()
                ->saveAddressCheckResult('shipping', $userId, $result, $mappedPersonStatus);
        }
        unset($session->moptPayoneShippingAddresscheckResult);
    }


    /**
     * check consumer score before payment choice if configured
     *
     * @param \Enlight_Hook_HookArgs $arguments
     */
    public function onShippingPaymentAction(\Enlight_Hook_HookArgs $arguments)
    {
        //TODO: Check if this is necessary
        return;
        $subject = $arguments->getSubject();
        /** @var \Mopt_PayoneMain $moptPayoneMain */
        $moptPayoneMain = $this->container->get('MoptPayoneMain');
        $config = $moptPayoneMain->getPayoneConfig(); // get global config

        if (!$config['consumerscoreActive']) {
            return;
        }

        $basketValue = $subject->View()->sAmount;
        $userData = $subject->View()->sUserData;
        $billingAddressData = $userData['billingaddress'];
        $billingAddressData['country'] = $billingAddressData['countryID'];
        $shippingAddressData = $userData['shippingaddress'];
        $shippingAddressData['country'] = $shippingAddressData['countryID'];
        $session = Shopware()->Session();
        $userId = $session->sUserId;

        if ($this->getCustomerCheckIsNeeded($config, $userId, $basketValue, false)) {
            // perform check if prechoice is configured
            if ($config['consumerscoreCheckMoment'] == 0) {
                try {
                    $response = $this->performConsumerScoreCheck($config, $billingAddressData, 0);
                    if (!$this->handleConsumerScoreCheckResult($response, $config, $userId)) {
                        // cancel, redirect to payment choice
                        if (version_compare(\Shopware::VERSION, '5.3.0', '>=')
                        ) {
                            $subject->forward('shippingPayment', 'checkout');
                        } else {
                            $subject->forward('payment', 'account', null, ['sTarget' => 'checkout']);
                        }
                    }
                } catch (\Exception $e) {
                    if ($config['consumerscoreFailureHandling'] == 0) {
                        // abort and delete payment data and set to payone prepayment
                        $moptPayoneMain->getPaymentHelper()->deletePaymentData($userId);
                        $moptPayoneMain->getPaymentHelper()->setConfiguredDefaultPaymentAsPayment($userId);
                        if (version_compare(\Shopware::VERSION, '5.3.0', '>=')
                        ) {
                            $subject->forward('shippingPayment', 'checkout', null);
                        } else {
                            $subject->forward('payment', 'account', null, ['sTarget' => 'checkout']);
                        }
                        return;
                    } else {
                        // continue

                        //$subject->forward('payment', 'account', null, ['sTarget' => 'checkout']);
                        return;
                    }
                }
            } else {
                // set sessionflag if after paymentchoice is configured
                $session->moptConsumerScoreCheckNeedsUserAgreement = true;
                $session->moptPaymentId = $subject->View()->sPayment['id'];
            }

        }
    }

    /**
     * Forward the request to the given controller, module and action with the given parameters.
     * copied from Enlight_Controller_Action
     * and customized
     *
     * @param mixed $request
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array  $params
     */
    public function forward($request, $action, $controller = null, $module = null, array $params = null)
    {
        if ($params !== null) {
            $request->setParams($params);
        }
        if ($controller !== null) {
            $request->setControllerName($controller);
            if ($module !== null) {
                $request->setModuleName($module);
            }
        }

        $request->setActionName($action)->setDispatched(false);
    }
}
