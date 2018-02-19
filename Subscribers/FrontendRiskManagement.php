<?php

namespace Shopware\FatchipCTPayment\Subscribers;

use Enlight\Event\SubscriberInterface;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTResponse;
use Shopware\Components\DependencyInjection\Container;
use Shopware\FatchipCTPayment\Util;

/**
 * Class AddressCheck
 *
 * @package Shopware\Plugins\MoptPaymentPayone\Subscribers
 */
class FrontendRiskManagement implements SubscriberInterface {

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
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents() {

        $events = ['sAdmin::executeRiskRule::replace' => 'sAdmin__executeRiskRule',];

        if (\Shopware::VERSION === '___VERSION___' || version_compare(\Shopware::VERSION, '5.2.0', '>='))
        {
            $events['Shopware\Models\Customer\Address::postUpdate'] = 'afterAddressUpdate';
        } else {
            $events['Shopware_Modules_Admin_ValidateStep2Shipping_FilterResult'] = 'onValidateStep2ShippingAddress';
            $events['Shopware_Modules_Admin_ValidateStep2_FilterResult'] = 'onValidateStep2BillingAddress';
        }

        return $events;
    }


    /***
     * @param \Enlight_Hook_HookArgs $args
     *
     * Fired after a user updates an address in SW >=5.2 If a CRIF result is available, it will be
     * invalidated / deleted
     */
    public function afterAddressUpdate(\Enlight_Hook_HookArgs $args) {
        //check in Session if we autoupated the address with the corrected Address from CRIF
        if (!$this->addressWasAutoUpdated()) {
            /** @var \Shopware\Models\Customer\Address $model */
            $model = $args->getEntity();
            $this->invalidateCrifFOrAddress($model);
        }
    }


    public function onValidateStep2BillingAddress(\Enlight_Hook_HookArgs $arguments) {
        //check in Session if we autoupated the address with the corrected Address from CRIF
        if (!$this->addressWasAutoUpdated()) {
            $session = Shopware()->Session();
            $orderVars = Shopware()->Session()->sOrderVariables;
            $userData = $orderVars['sUserData'];
            $oldBillingAddress = $userData['billingaddress'];
            $customerBillingId = $userData['billingaddress']['customerBillingId'];

            //postdata contains the new addressdata that the user just entered
            $postData       = $arguments->get('post');

            if (!empty($customerBillingId) && $this->addressChanged($postData, $oldBillingAddress)) {
                $this->invalidateCrifResult($customerBillingId, 'billing');
            }
        }
    }

    public function onValidateStep2ShippingAddress(\Enlight_Hook_HookArgs $arguments)
    {
        //check in Session if we autoupated the address with the corrected Address from CRIF
        if (!$this->addressWasAutoUpdated()) {
            $session = Shopware()->Session();
            $orderVars = Shopware()->Session()->sOrderVariables;
            $userData = $orderVars['sUserData'];
            $oldShippingAddress = $userData['shippingaddress'];
            $customerShippingId = $userData['shippingaddress']['customerShippingId'];

            //postdata contains the new addressdata that the user just entered
            $postData       = $arguments->get('post');

            if (!empty($customerShippingId) && $this->addressChanged($postData, $oldShippingAddress)) {
                $this->invalidateCrifResult($customerShippingId, 'shipping');
            }
        }
    }

    private function addressChanged($oldAddress, $newAddress) {
        //we consider the address changed if street, zipcode, city or country changed
        return ($oldAddress['city'] !== $newAddress['city'] || $oldAddress['street'] !== $newAddress['street'] ||
          $oldAddress['zipcode'] !== $newAddress['zipcode'] || $oldAddress['country'] !== $newAddress['countryID']);
    }


    /**
     * prepare Computop risk checks
     *
     * @param \Enlight_Hook_HookArgs $arguments
     * @return boolean
     */
    public function sAdmin__sManageRisks__before(\Enlight_Hook_HookArgs $arguments) {
        Shopware()->Session()->CTRiskCheckPaymentId = $arguments->get('paymentID');
    }

    /**
     * clean up Computop risk checks
     *
     * @param \Enlight_Hook_HookArgs $arguments
     * @return boolean
     */
    public function sAdmin__sManageRisks__after(\Enlight_Hook_HookArgs $arguments) {
        unset(Shopware()->Session()->CTRiskCheckPaymentId);
    }


    /**
     * @param \Shopware\Models\Customer\Address $address
     *
     * removes CRIF results from Address
     */
    private function invalidateCrifFOrAddress($address) {
        /* @var \Shopware\Models\Customer\Address $address */
        if ($attribute = $address->getAttribute()) {
            $attribute->setFatchipctCrifdate(0);
            $attribute->setFatchipctCrifdescription(null);
            $attribute->setFatchipctCrifresult(null);
            $attribute->setFatchipctCrifstatus(null);
            Shopware()->Models()->persist($attribute);
            Shopware()->Models()->flush();
        }
    }

    private function invalidateCrifResult($addressID, $type) {
        $util = new Util();
        $address = $util->getCustomerAddressById($addressID, $type);
        if ($attribute = $address->getAttribute()) {
            $attribute->setFatchipctCrifdate(0);
            $attribute->setFatchipctCrifdescription(null);
            $attribute->setFatchipctCrifresult(null);
            $attribute->setFatchipctCrifstatus(null);
            Shopware()->Models()->persist($attribute);
            Shopware()->Models()->flush();
        }
    }

    /**
     * handle rules beginning with 'sRiskMOPT_PAYONE__'
     * returns true if risk condition is fulfilled
     * arguments: $rule, $user, $basket, $value
     *
     * * @param \Enlight_Hook_HookArgs $arguments
     */
    public function sAdmin__executeRiskRule(\Enlight_Hook_HookArgs $arguments) {
        $rule = $arguments->get('rule');

        $user = $arguments->get('user');

        // execute parent call if rule is not Computop
        if (strpos($rule, 'sRiskFATCHIP_COMPUTOP__') !== 0) {
            $arguments->setReturn(
              $arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
              )
            );
        }
        else {

            /** @var \Fatchip\CTPayment\CTPaymentService $service */
            $service = Shopware()->Container()->get('FatchipCTPaymentApiClient');
            $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
            $config = $plugin->Config()->toArray();
            //only execute riskcheck if a CRIF method is set in config.
            if (!isset($config['crifmethod']) || $config['crifmethod'] == 'inactive') {
                $arguments->setReturn(FALSE);

                return;
            }


            //$value contains the value that we want to compare with, as set in the SW Riskmanagment Backend Rule
            $value = $arguments->get('value');
            $basket = $arguments->get('basket');
            $user = $arguments->get('user');

            $userId = $user['additional']['user']['id'] ? $user['additional']['user']['id'] : null;
            $userObject = $userId ? Shopware()->Models()
              ->getRepository('Shopware\Models\Customer\Customer')
              ->find($userId) : null;

            //If we don't have a userobject yet, there is no point in doing a risk check
            if (!$userObject) {
                $arguments->setReturn(FALSE);

                return;
            }

            //only make a call to the CRIF service if Necessary
            if ($this->crifCheckNecessary($user['billingaddress'], 'billing')) {

                $billingAddressData = $user['billingaddress'];
                $billingAddressData['country'] = $billingAddressData['countryId'];
                $shippingAddressData = $user['shippingaddress'];
                $shippingAddressData['country'] = $billingAddressData['countryId'];

                $util = new Util();

                $ctOrder = new CTOrder();
                $ctOrder->setAmount($basket['AmountNumeric'] * 100);
                $ctOrder->setCurrency( Shopware()->Container()->get('currency')->getShortName());
                $ctOrder->setBillingAddress($util->getCTAddress($user['billingaddress']));
                $ctOrder->setShippingAddress($util->getCTAddress($user['shippingaddress']));
                $ctOrder->setEmail($user['additional']['user']['email']);
                $ctOrder->setCustomerID($user['additional']['user']['id']);

                //TODO: Set orderDesc and Userdata
                $crif = $service->getCRIFClass($config, $ctOrder, 'testOrder', 'testUserData');
                //make the call to CRIF
                $rawResp = $crif->callCRFDirect();
                /** @var \Fatchip\CTPayment\CTResponse\CTResponse $crifResponse */
                $crifResponse = $service->createPaymentResponse($rawResp);
                $status = $crifResponse->getStatus();
                $callResult = $crifResponse->getResult();
                //write the result to the session for this billingaddressID
                $crifInformation[$billingAddressData['id']] = $this->getCRIFResponseArray($crifResponse);
                //and save the resul in the billingaddress
                $util->saveCRIFResultInAddress($billingAddressData['id'], 'billing', $crifResponse);
                //$util->saveCRIFResultInAddress($shippingAddressData['id'], 'shipping', $crifResponse);

                //if set in Plugin settings, we have to update the address with the corrected Addressdate
                $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
                $config = $plugin->Config()->toArray();
                if ($config['bonitaetusereturnaddress']) {
                    $this->updateBillingAddressFromCrifResponse($billingAddressData['id'], $crifResponse);
                }

            }
            else {
                $callResult = $this->getCrifResultFromAddressArray($user['billingaddress']);
            }

            if ($this->$rule($callResult, $value)) {
                $arguments->setReturn(TRUE);

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


    /***
     * @param $addressArray
     * @param null $type: billing or shipping
     * @return bool
     */
    private function crifCheckNecessary($addressArray, $type = null) {

        $crifStatus = $this->getCrifStatusFromAddressArray($addressArray);
        $crifDate =  $this->getCrifDateFromAddressArray($addressArray);
        $crifResult = $this->getCrifResultFromAddressArray($addressArray);
        //if crif is not responding (FAILED), we try again after one hour to prevent making hundreds of calls
        //In Adressarray there are underscores in attribute nmaes
        if ($crifStatus == 'FAILED') {
            $lastTimeChecked = $this->getCrifDateFromAddressArray($addressArray);
            $hoursPassed = $lastTimeChecked->diff(new \DateTime('now'), true)->hours;
            return $hoursPassed > 1;
        }

        $util = new Util();
        //check in Session if CRIF data are missing.
        if (!isset($crifResult))
        {
            //If it is not in the session, we also check in the database to prevent multiple calls
            if (isset($addressArray['id'])) {
                $address = $util->getCustomerAddressById($addressArray['id'], $type);
                if (!empty($address) && $attribute = $address->getAttribute()) {
                    $attributeData = Shopware()->Models()->toArray($address->getAttribute());
                    //in attributeData there are NO underscores in attribute names and Shopware ads CamelCase after fcct prefix
                    if (!isset($attributeData['fcctCrifresult'])|| !isset($attributeData['fcctCrifdate'])) {
                        return true;
                    }
                    else {
                        //write the values from the database in the addressarray
                        $addressArray['attribute']['fcct_crifresult'] = $attributeData['fcctCrifresult'];
                        $addressArray['attribute']['fcct_crifdate'] = $attributeData['fcctCrifdate'];
                    }
                } else {
                    return false;
                }
            }
        }

        //if CRIF data IS saved in both addresses, check if the are expired,
        //that means, they are older then the number of days set in Pluginsettings
        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        $config = $plugin->Config()->toArray();
        $invalidateAfterDays = $config['bonitaetinvalidateafterdays'];
        if (is_numeric($invalidateAfterDays) && $invalidateAfterDays > 0) {
            /** @var \DateTime $lastTimeChecked */
            $lastTimeChecked = $this->getCrifDateFromAddressArray($addressArray);

            $daysPassed = $lastTimeChecked->diff(new \DateTime('now'), true)->days;

            if ($daysPassed > $invalidateAfterDays) {
                return true;
            }
        }

        return false;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 -
    // 5.2 -
    // 5.3 - check
    private function getCrifStatusFromAddressArray($aAddress) {
        if (array_key_exists('fatchipctCrifstatus' , $aAddress)) {
            return $aAddress['fatchipctCrifstatus'];
        } else if (array_key_exists('fatchipct_crifstatus', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipct_crifstatus'];
        } else if (array_key_exists('fatchipctCrifstatus', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipctCrifstatus'];
        }
        return null;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 -
    // 5.1 -
    // 5.2 -
    // 5.3 -
    private function getCrifResultFromAddressArray($aAddress) {
        if (array_key_exists('fatchipctCrifresult' , $aAddress)) {
            return $aAddress['fatchipctCrifresult'];
        } else if (array_key_exists('fatchipct_crifresult', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipct_crifresult'];
        } else if (array_key_exists('fatchipctCrifresult', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipctCrifresult'];
        }
        return null;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 -
    // 5.1 -
    // 5.2 -
    // 5.3 -
    private function getCrifDateFromAddressArray($aAddress) {

        if (array_key_exists('fatchipctCrifdate' , $aAddress)) {
            return $aAddress['fatchipctCrifdate'] instanceof \DateTime ?
              $aAddress['fatchipctCrifdate'] : new \DateTime($aAddress['fatchipctCrifdate']);
        } else if (array_key_exists('fatchipct_crifdate', $aAddress['attributes'])) {
            return  $aAddress['attributes']['fatchipct_crifdate'] instanceof \DateTime ?
              $aAddress['attributes']['fatchipct_crifdate'] : new \DateTime($aAddress['attributes']['fatchipct_crifdate']);

        } else if (array_key_exists('fatchipctCrifdate', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipctCrifdate'] instanceof \DateTime ?
              $aAddress['attributes']['fatchipctCrifdate'] : new \DateTime($aAddress['attributes']['fatchipctCrifdate']);
        }
        return null;
    }


    /**
     * check if user score equals configured score to block payment method
     *
     * @param $scoring
     * @param $value
     * @return bool
     */
    public function sRiskFATCHIP_COMPUTOP__TRAFFIC_LIGHT_IS($scoring, $value) {
        return $scoring == $value; //return true if payment has to be denied
    }


    /**
     * check if user score equals not configured score to block payment method
     *
     * @param $scoring
     * @param $value
     * @return bool
     */
    public function sRiskFATCHIP_COMPUTOP__TRAFFIC_LIGHT_IS_NOT($scoring, $value) {
        return !$this->sRiskFATCHIP_COMPUTOP__TRAFFIC_LIGHT_IS($scoring, $value);
    }

    /***
     * @param $addressID
     * @param $crifResponse CTResponse
     */
    private function updateBillingAddressFromCrifResponse($addressID, $crifResponse) {
        $util = new Util();
        if ($address = $util->getCustomerAddressById($addressID, 'billing')) {
            //only update the address, if something changed. This check is important, because if nothing changed
            //callin persist and flush does not result in calling afterAddressUpdate and the session variable
            //fatchipComputopCrifAutoAddressUpdate woould not get cleared.
            if ($address->getFirstName() !== $crifResponse->getFirstName() ||
              $address->getLastName() !== $crifResponse->getLastName() ||
              $address->getStreet() != $crifResponse->getAddrStreet() . ' ' . $crifResponse->getAddrStreetNr() ||
              $address->getZipCode() !== $crifResponse->getAddrZip() ||
              $address->getCity() !== $crifResponse->getAddrCity()
            ) {
                $address->setFirstName($crifResponse->getFirstName());
                $address->setLastName($crifResponse->getLastName());
                $address->setStreet($crifResponse->getAddrStreet() . ' ' . $crifResponse->getAddrStreetNr());
                $address->setCity($crifResponse->getAddrCity());
                $address->setZipcode($crifResponse->getAddrZip());
                //TODO: country

                //Write to session that this address is autmatically changed, so we do not fire a second CRIF request
                $session = Shopware()->Session();
                $session->offsetSet('fatchipComputopCrifAutoAddressUpdate', $addressID);

                Shopware()->Models()->persist($address);
                Shopware()->Models()->flush();
            }
        }
    }

    private function addressWasAutoUpdated() {
        if (Shopware()->Session()->offsetExists('fatchipComputopCrifAutoAddressUpdate')) {
            Shopware()->Session()->offsetUnset('fatchipComputopCrifAutoAddressUpdate');
            return true;
        }

        return false;
    }
}
