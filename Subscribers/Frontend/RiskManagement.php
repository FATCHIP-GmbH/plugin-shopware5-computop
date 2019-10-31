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

use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTCrif\CRIF;
use Fatchip\CTPayment\CTPaymentService;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Plugins\FatchipCTPayment\Subscribers\AbstractSubscriber;
use Shopware\Plugins\FatchipCTPayment\Util;

/**
 * Class FrontendRiskManagement
 *
 * @package Shopware\Plugins\MoptPaymentPayone\Subscribers
 */
class RiskManagement extends AbstractSubscriber
{

    const allowedCountries = ['DE', 'AT', 'CH', 'NL'];

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

        $events = ['sAdmin::executeRiskRule::replace' => 'onExecuteRiskRule',];

        if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $events['Shopware\Models\Customer\Address::preUpdate'] = 'beforeAddressUpdate';
        } else {
            $events['Shopware_Modules_Admin_ValidateStep2Shipping_FilterResult'] = 'onValidateStep2ShippingAddress';
            $events['Shopware_Modules_Admin_ValidateStep2_FilterResult'] = 'onValidateStep2BillingAddress';
        }

        return $events;
    }


    /**
     * Fired after a user updates an address in SW >=5.2
     * If a CRIF result is available, it will be invalidated / deleted
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function beforeAddressUpdate(\Enlight_Event_EventArgs $args)
    {
        //check in Session if we autoupated the address with the corrected Address from CRIF
        if (!$this->addressWasAutoUpdated()) {
            /** @var \Shopware\Models\Customer\Address $model */
            $model = $args->getEntity();
            $this->invalidateCrifFOrAddress($model);
        }
    }


    /**
     * Fired after a user updates a billing address in SW < 5.2
     * If a CRIF result is available, it will be invalidated / deleted
     *
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onValidateStep2BillingAddress(\Enlight_Event_EventArgs $arguments)
    {
        //check in Session if we autoupated the address with the corrected Address from CRIF
        if (!$this->addressWasAutoUpdated()) {
            $session = Shopware()->Session();
            $orderVars = Shopware()->Session()->sOrderVariables;
            $userData = $orderVars['sUserData'];
            $oldBillingAddress = $userData['billingaddress'];
            $customerBillingId = $userData['billingaddress']['customerBillingId'];

            //postdata contains the new addressdata that the user just entered
            $postData = $arguments->get('post');

            if (!empty($customerBillingId) && $this->addressChanged($postData, $oldBillingAddress)) {
                $this->invalidateCrifResult($customerBillingId, 'billing');
            }
        }
    }

    /**
     * Fired after a user updates a shipping address in SW < 5.2
     * If a CRIF result is available, it will be invalidated / deleted
     *
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onValidateStep2ShippingAddress(\Enlight_Event_EventArgs $arguments)
    {
        //check in Session if we autoupated the address with the corrected Address from CRIF
        if (!$this->addressWasAutoUpdated()) {
            $session = Shopware()->Session();
            $orderVars = Shopware()->Session()->sOrderVariables;
            $userData = $orderVars['sUserData'];
            $oldShippingAddress = $userData['shippingaddress'];
            $customerShippingId = $userData['shippingaddress']['customerShippingId'];

            //postdata contains the new addressdata that the user just entered
            $postData = $arguments->get('post');

            if (!empty($customerShippingId) && $this->addressChanged($postData, $oldShippingAddress)) {
                $this->invalidateCrifResult($customerShippingId, 'shipping');
            }
        }
    }

    /**
     * TODO: move to general helper
     *
     * Checks if relevant parts of an address have changed that make a new CRIF-Rikscheck necessary.
     * @param $oldAddress
     * @param $newAddress
     * @return bool
     */
    private function addressChanged($oldAddress, $newAddress)
    {
        //we consider the address changed if street, zipcode, city or country changed
        return ($oldAddress['city'] !== $newAddress['city'] || $oldAddress['street'] !== $newAddress['street'] ||
            $oldAddress['zipcode'] !== $newAddress['zipcode'] || $oldAddress['country'] !== $newAddress['countryID']);
    }

    /**
     * TODO: move to general helper
     *
     * removes CRIF results from Address.
     * @param \Shopware\Models\Customer\Address $address
     */
    private function invalidateCrifFOrAddress($address)
    {
        /* @var \Shopware\Models\Customer\Address $address */
        if ($attribute = $address->getAttribute()) {
            $attribute->setFatchipctCrifdate(0);
            $attribute->setFatchipctCrifdescription(null);
            $attribute->setFatchipctCrifresult(null);
            $attribute->setFatchipctCrifstatus(null);
            Shopware()->Models()->persist($attribute);
        }
    }

    /**
     * TODO: move to general helper
     *
     * Removes CRIF results from address with addresID.
     * @param $addressID
     * @param $type
     */
    private function invalidateCrifResult($addressID, $type)
    {
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
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onExecuteRiskRule(\Enlight_Event_EventArgs $arguments)
    {
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
        } else {

            /** @var CTPaymentService $service */
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
                $ctOrder->setCurrency(Shopware()->Container()->get('currency')->getShortName());
                $ctOrder->setEmail($user['additional']['user']['email']);
                $ctOrder->setCustomerID($user['additional']['user']['id']);

                try {
                    $ctOrder->setBillingAddress($util->getCTAddress($user['billingaddress']));
                    $ctOrder->setShippingAddress($util->getCTAddress($user['shippingaddress']));
                } catch (\Exception $e) {
                    // fail risk checks if address has errors
                    $arguments->setReturn(TRUE);
                    return;

                }
                //TODO: Set orderDesc
                /** @var CRIF $crif */
                $crif = $service->getCRIFClass($config, $ctOrder, 'testOrder', $this->getUserDataParam());
                $crifParams = $crif->getRedirectUrlParams();
                $crifResponse = $plugin->callComputopService($crifParams, $crif, 'CRIF', $crif->getCTPaymentURL());

                /** @var \Fatchip\CTPayment\CTResponse $crifResponse */
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

            } else {
                $callResult = $this->getCrifResultFromAddressArray($user['billingaddress']);
            }

            if ($this->$rule($callResult, $value)) {
                $arguments->setReturn(TRUE);

                return;
            }
        }
    }

    /**
     * TODO: move to general helper
     *
     * Returns CRIF results in an array.
     * @param $crifResponseObject
     * @return array
     */
    private function getCRIFResponseArray($crifResponseObject)
    {
        $crifResponseArray = array();
        $crifResponseArray['Code'] = $crifResponseObject->getCode();
        $crifResponseArray['Description'] = $crifResponseObject->getDescription();
        $crifResponseArray['result'] = $crifResponseObject->getResult();
        $crifResponseArray['status'] = $crifResponseObject->getStatus();

        return $crifResponseArray;
    }

    /**
     * TODO: move to general helper
     *
     * Checks if a CRIF risk check is necessary:
     * 1. Only if billing country in allowed countries array because for other countries crif has no data available
     * 2. If a call failed we wait one hour to try again, to prevent making hundreds of calls within minutes
     * 3. Check both in session and in database. If no crif results in both, check is necessary
     * 4. If a CRIF result exists, check if it is expired according to plugin settings
     *
     * @param $addressArray
     * @param null $type : billing or shipping
     * @return bool
     */
    private function crifCheckNecessary($addressArray, $type = null)
    {

        $crifStatus = $this->getCrifStatusFromAddressArray($addressArray);
        $crifDate = $this->getCrifDateFromAddressArray($addressArray);
        $crifResult = $this->getCrifResultFromAddressArray($addressArray);

        // only check in allowed countries self::allowedCountries
        $util = new Util();
        $countryIso = $util->getCTCountryIso($util->getCountryIdFromAddress($addressArray));
        $allowedCountry = in_array($countryIso, self::allowedCountries) ? true : false;
        if (! $allowedCountry){
            return false;
        }

        //if crif is not responding (FAILED), or INVALID (0) we try again after one hour to prevent making hundreds of calls
        //In Adressarray there are underscores in attribute nmaes
        if ($crifStatus == 'FAILED' || $crifStatus == '0') {
            $lastTimeChecked = $this->getCrifDateFromAddressArray($addressArray);
            $hoursPassed = $lastTimeChecked->diff(new \DateTime('now'), true)->hours;
            return $hoursPassed > 1;
        }

        $util = new Util();
        //check in Session if CRIF data are missing.
        if (!isset($crifResult)) {
            //If it is not in the session, we also check in the database to prevent multiple calls
            if (isset($addressArray['id'])) {
                $address = $util->getCustomerAddressById($addressArray['id'], $type);
                if (!empty($address) && $attribute = $address->getAttribute()) {
                    $attributeData = Shopware()->Models()->toArray($address->getAttribute());
                    //in attributeData there are NO underscores in attribute names and Shopware ads CamelCase after fcct prefix
                    if (!isset($attributeData['fatchipctCrifresult']) || !isset($attributeData['fatchipctCrifdate'])) {
                        return true;
                    } else {
                        //write the values from the database in the addressarray, as they are needed later to check last time checked
                        $addressArray['attributes']['fatchipctCrifresult'] = $attributeData['fatchipctCrifresult'];
                        $addressArray['attributes']['fatchipctCrifdate'] = $attributeData['fatchipctCrifdate'];
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

    /**
     * TODO: move to general helper
     *
     * Returns crifstatus from an addressarray, depending on SW version.
     * @param $aAddress
     * @return null
     */
    private function getCrifStatusFromAddressArray($aAddress)
    {
        // SW 5.0
        if (array_key_exists('fatchipCTCrifstatus', $aAddress)) {
            return $aAddress['fatchipCTCrifstatus'];
        } // SW 5.3, SW 5.4
        else if (array_key_exists('fatchipct_crifstatus', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipct_crifstatus'];
        } //Also SW 5.4
        else if (array_key_exists('fatchipctCrifstatus', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipctCrifstatus'];
        }
        // SW 5.
        else if (array_key_exists('fatchipCT_crifstatus', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipCT_crifstatus'];
        } // SW 5.1
        else if (array_key_exists('fatchipctCrifstatus', $aAddress)) {
            return $aAddress['fatchipctCrifstatus'];
        }
        return null;
    }

    /**
     * Returns CRIF result from an addressarray, depending on SW version.
     * @param $aAddress
     * @return null
     */
    private function getCrifResultFromAddressArray($aAddress)
    {
        // SW 5.0
        if (array_key_exists('fatchipCTCrifresult', $aAddress)) {
            return $aAddress['fatchipCTCrifresult'];
        } // SW 5.3, SW 5.4
        else if (array_key_exists('fatchipct_crifresult', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipct_crifresult'];
        } // SW 5.2
        else if (array_key_exists('fatchipCT_crifresult', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipCT_crifresult'];
        } // SW 5.1fatchipct
        else if (array_key_exists('fatchipctCrifresult', $aAddress)) {
            return $aAddress['fatchipctCrifresult'];
        }
        return null;
    }

    /**
     * TODO: move to general helper
     *
     * Returns CRIF date from an addressarray, depending on SW version.
     * @param $aAddress
     * @return \DateTime|null
     */
    private function getCrifDateFromAddressArray($aAddress)
    {
        /*not SW dependent, but might have been set in crifCheckNecessary*/
        if (array_key_exists('fatchipctCrifdate', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipctCrifdate'] instanceof \DateTime ?
              $aAddress['attributes']['fatchipctCrifdate'] : new \DateTime($aAddress['attributes']['fatchipctCrifdate']);
        }
        // SW 5.0
        else if (array_key_exists('fatchipCTCrifdate', $aAddress)) {
            return $aAddress['fatchipCTCrifdate'] instanceof \DateTime ?
                $aAddress['fatchipCTCrifdate'] : new \DateTime($aAddress['fatchipCTCrifdate']);
        } // SW 5.3, SW 5.4
        else if (array_key_exists('fatchipct_crifdate', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipct_crifdate'] instanceof \DateTime ?
                $aAddress['attributes']['fatchipct_crifdate'] : new \DateTime($aAddress['attributes']['fatchipct_crifdate']);
        } // SW 5.2
        else if (array_key_exists('fatchipCT_crifdate', $aAddress['attributes'])) {
            return $aAddress['attributes']['fatchipCT_crifdate'] instanceof \DateTime ?
                $aAddress['attributes']['fatchipCT_crifdate'] : new \DateTime($aAddress['attributes']['fatchipCT_crifdate']);
        } // SW 5.1
        else if (array_key_exists('fatchipctCrifdate', $aAddress)) {
            return $aAddress['fatchipctCrifdate'] instanceof \DateTime ?
                $aAddress['fatchipctCrifdate'] : new \DateTime($aAddress['fatchipctCrifdate']);
        }
        return null;
    }


    /**
     * TODO: move to general helper
     *
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
     * TODO: move to general helper
     *
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
     * TODO: move to general helper
     *
     * Updates billingaddress from address in CRIF response, but only if something has changed.
     *
     * @param $addressID
     * @param $crifResponse CTResponse
     */
    private function updateBillingAddressFromCrifResponse($addressID, $crifResponse)
    {
        $util = new Util();
        if ($address = $util->getCustomerAddressById($addressID, 'billing')) {
            //only update the address, if something changed. This check is important, because if nothing changed
            //callin persist and flush does not result in calling afterAddressUpdate and the session variable
            //fatchipComputopCrifAutoAddressUpdate woould not get cleared.
            //also check if crifResponse contains addressdata at all, because an internal server error sometimes returns empty
            //address data, which would result in clearing the billing address.
            if ($this->crifResponseContainsAdressdata($crifResponse) &&
                    (   $address->getFirstName() !== $crifResponse->getFirstName() ||
                        $address->getLastName() !== $crifResponse->getLastName() ||
                        $address->getStreet() != $crifResponse->getAddrStreet() . ' ' . $crifResponse->getAddrStreetNr() ||
                        $address->getZipCode() !== $crifResponse->getAddrZip() ||
                        $address->getCity() !== $crifResponse->getAddrCity()
                    )
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

    //TODO: move to general helper
    private function crifResponseContainsAdressdata($crifResponse) {
        return strlen($crifResponse->getAddrStreet()) > 0;
    }

    /**
     * TODO: move to general helper
     *
     * Checks in session if adress was auto updated. This happens if CRIF corrected an adress
     * and it is activated in pluginsettings that corrected addresses should be used.
     * @return bool
     */
    private function addressWasAutoUpdated()
    {
        if (Shopware()->Session()->offsetExists('fatchipComputopCrifAutoAddressUpdate')) {
            Shopware()->Session()->offsetUnset('fatchipComputopCrifAutoAddressUpdate');
            return true;
        }

        return false;
    }

    /**
     * TODO: move to general helper
     *
     * Duplicate methods from payment controller
     * Sets the userData paramater for Computop calls to Shopware Version and Module Version
     * @return string
     */
    private function getUserDataParam()
    {
        $plugin = Shopware()->Plugins()->Frontend()->FatchipCTPayment();
        return  'Shopware Version: ' .  Util::getShopwareVersion() . ', Modul Version: ' . $plugin->getVersion() ;;
    }
}
