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

namespace Shopware\Plugins\FatchipCTPayment;

use Doctrine\ORM\OptimisticLockException;
use Exception;
use Fatchip\CTPayment\CTAddress\CTAddress;
use Fatchip\CTPayment\CTOrder\CTOrder;
use Fatchip\CTPayment\CTPaymentMethods\KlarnaPayments;
use Fatchip\CTPayment\CTResponse;
use Shopware\Components\Logger;
use Shopware\Models\Customer\Customer;
use Shopware_Plugins_Frontend_FatchipCTPayment_Bootstrap;
use VIISON\AddressSplitter\AddressSplitter;
use Shopware;

require_once 'Components/Api/vendor/autoload.php';

/**
 * Class Util
 * @package Shopware\Plugins\FatchipCTPayment
 */
class Util
{
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct()
    {
        $this->logger = new Logger('FatchipCTPayment');
    }

    public static function getShopwareVersion() {
        $currentVersion = '';

        if(defined('\Shopware::VERSION')) {
            $currentVersion = \Shopware::VERSION;
        }

        //get old composer versions
        if($currentVersion === '___VERSION___' && class_exists('ShopwareVersion') && class_exists('PackageVersions\Versions')) {
            $currentVersion = \ShopwareVersion::parseVersion(
                \PackageVersions\Versions::getVersion('shopware/shopware')
            )['version'];
        }

        if(!$currentVersion || $currentVersion === '___VERSION___') {
            $currentVersion = Shopware()->Container()->getParameter('shopware.release.version');
        }

        return $currentVersion;
    }

    /**
     *
     * @param $compareVersion
     *
     * @return bool
     */
    public static function isShopwareVersionGreaterThanOrEqual($compareVersion)
    {
        $currentVersion = self::getShopwareVersion();

        return version_compare($currentVersion, $compareVersion, '>=');
    }

    /**
     * creates a CTAddress object from a Shopware address array
     * @param array $swAddress
     * @return CTAddress
     * @throws \Exception
     */
    public function getCTAddress(array $swAddress)
    {
        $splitAddress = AddressSplitter::splitAddress($swAddress['street']);

        return new CTAddress(
            ($swAddress['salutation'] == 'mr') ? 'Herr' : 'Frau',
            $swAddress['company'],
            ($swAddress['firstname']) ? $swAddress['firstname'] : $swAddress['firstName'],
            ($swAddress['lastname']) ? $swAddress['lastname'] : $swAddress['lastName'],
            $splitAddress['streetName'],
            $splitAddress['houseNumber'],
            ($swAddress['zipcode']) ? $swAddress['zipcode'] : $swAddress['zipCode'],
            $swAddress['city'],
            $this->getCTCountryIso($this->getCountryIdFromAddress($swAddress)),
            $this->getCTCountryIso3($this->getCountryIdFromAddress($swAddress)),
            // ToDo does this correspond to additional_address_lines?
            $swAddress['additional_address_line1']
        );
    }

    /**
     * Use getCountryIdFromAddress() to get the countryId
     * for all Shopware Versions
     * @param $countryId
     * @return string
     */
    public function getCTCountryIso($countryId)
    {
        $countrySql = 'SELECT countryiso FROM s_core_countries WHERE id=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryId]);
    }

    /**
     * @ignore <description>
     * @param $countryId
     * @return string
     */
    public function getCTCountryIso3($countryId)
    {
        $countrySql = 'SELECT iso3 FROM s_core_countries WHERE id=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryId]);
    }

    /**
     * @ignore <description>
     * @param $countryIso
     * @return string
     */
    public function getCountryIdFromIso($countryIso)
    {
        $countrySql = 'SELECT id FROM s_core_countries WHERE countryiso=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryIso]);
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * gets the user customer number from user array, depending on SW version
     * @param $user
     * @return null
     */
    public function getUserCustomerNumber($user)
    {
        $customerNumber = null;
        if (self::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $customerNumber = $user['billing']['customernumber'];
        } else {
            $customerNumber = $user['billingaddress']['customernumber'];
        }
        return $customerNumber;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * gets the users Date of Birth from user array, depending on SW version
     * @param $user
     * @return null
     */
    public function getUserDoB($user)
    {
        $birthdate = null;
        if (self::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $birthdate = isset($user['billing']['birthday']) ? $user['billing']['birthday'] : $user['additional']['user']['birthday'];
        } else {
            $birthdate = $user['billingaddress']['birthday'];
        }
        return $birthdate;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * gets user phone number from database
     * @param $user
     * @return mixed
     */
    public function getUserPhone($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        if (self::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $billing = $user->getDefaultBillingAddress();
        } else {
            $billing = $user->getBilling();
        }

        return $billing->getPhone();
    }

    /**
     * gets Users Socical Security Number from Customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserSSN($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        if ($attribute === null) {
            return null;
        }

        return $attribute->getFatchipctSocialsecuritynumber();
    }

    /**
     * gets users Annual Salary from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserAnnualSalary($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        if ($attribute === null) {
            return null;
        }

        return $attribute->getFatchipctAnnualSalary();
    }

    /**
     * gets Bank for Lastschrift from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserLastschriftBank($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        if ($attribute === null) {
            return null;
        }
        return $attribute->getFatchipctLastschriftbank();
    }

    /**
     * gets Iban for Lastschrift from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserLastschriftIban($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        if ($attribute === null) {
            return null;
        }
        return $attribute->getFatchipctLastschriftiban();
    }

    /**
     * gets Iban for Afterpay from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserAfterpayInstallmentIban($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        if ($attribute === null) {
            return null;
        }
        return $attribute->getFatchipctAfterpayinstallmentiban();
    }

    /**
     * gets Accountowner for Lastschrift from customer attributes
     * @param $user
     * @return mixed
     */
    public function getUserLastschriftKontoinhaber($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        if ($attribute === null) {
            return null;
        }
        return $attribute->getFatchipctLastschriftaccowner();
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * gets CountryID from shopware address
     * @param $swAddress
     * @return null
     */
    public function getCountryIdFromAddress($swAddress)
    {
        $countryId = null;
        /*
        if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $countryId = $swAddress['countryId'];
        } else {
            $countryId = $swAddress['countryID'];
        }
        */
        $countryId = ($swAddress['countryId']) ? $swAddress['countryId'] : $swAddress['countryID'];
        return $countryId;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    /**
     * updates users Date of Birth in Customer atrributes
     * @param $userId
     * @param $birthday
     */
    public function updateUserDoB($userId, $birthday)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $user->setBirthday($birthday);
            Shopware()->Models()->persist($user);
            Shopware()->Models()->flush($user);

        } else {
            $billing = $user->getBilling();
            $billing->setBirthday($birthday);
            Shopware()->Models()->persist($billing);
            Shopware()->Models()->flush($billing);
        }
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 -
    // 5.3 -
    /**
     * updates users phone number in customer attributes
     * @param $userId
     * @param $phone
     */
    public function updateUserPhone($userId, $phone)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        if (Util::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $billing = $user->getDefaultBillingAddress();
        } else {
            $billing = $user->getBilling();
        }
        $billing->setPhone($phone);
        Shopware()->Models()->persist($billing);
        Shopware()->Models()->flush($billing);
    }


    /**
     * updates users Social Security Number in customer attributes
     * @param $userId
     * @param $ssn
     */
    public function updateUserSSN($userId, $ssn)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctSocialsecuritynumber($ssn);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates users Annual Salary in customer attributes
     * @param $userId
     * @param $ssn
     */
    public function updateUserAnnualSalary($userId, $ssn)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctAnnualSalary($ssn);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates users Bank for Lastschrift in customer attributes
     * @param $userId
     * @param $bank
     */
    public function updateUserLastschriftBank($userId, $bank)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctLastschriftbank($bank);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates users Iban for lastschrift in customer attributes
     * @param $userId
     * @param $iban
     */
    public function updateUserLastschriftIban($userId, $iban)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctLastschriftiban($iban);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates users Iban for Afterpay in customer attributes
     * @param $userId
     * @param $iban
     */
    public function updateUserAfterpayInstallmentIban($userId, $iban)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctAfterpayinstallmentiban($iban);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates user account owner in customer attributes
     * @param $userId
     * @param $kontoinhaber
     */
    public function updateUserLastschriftKontoinhaber($userId, $kontoinhaber)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipctLastschriftaccowner($kontoinhaber);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * Loads customer adress by ID
     * @param $id
     * @param $type
     * @return null|object
     */
    public function getCustomerAddressById($id, $type)
    {
        if ( ! self::isShopwareVersionGreaterThanOrEqual('5.2')) {
            $address = $type == 'shipping' ? $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Shipping')->find($id) :
                $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Billing')->find($id);
        } else {
            $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address')->find($id);
        }
        return $address;
    }

    /**
     * Returns an array with all activated Klarna payment names, such as
     * 'fatchip_computop_klarna_slice_it'
     *
     * @return array
     */
    public function getActivatedKlarnaPaymentTypes()
    {
        $sql = 'SELECT name FROM s_core_paymentmeans WHERE name like "%klarna%"';

        $result = Shopware()->Db()->fetchCol($sql);

        foreach ($result as $key => $name) {
            $result[$key] = $this->getKlarnaPaymentTypeFromPaymentName($name);
        }

        return $result;
    }

    /**
     * @param $paymentName
     * @return string
     */
    public function getKlarnaPaymentTypeFromPaymentName($paymentName)
    {
        $paymentNamePrefix = 'fatchip_computop_klarna_';
        return substr($paymentName, strlen($paymentNamePrefix));
    }

    /**
     * returns payment name
     *
     * @param string $paymentID
     * @return string
     */
    public function getPaymentNameFromId($paymentID)
    {
        $sql = 'SELECT `name` FROM `s_core_paymentmeans` WHERE id = ?';
        return Shopware()->Db()->fetchOne($sql, $paymentID);
    }

    /**
     * returns payment name
     *
     * @param string $paymentName
     * @return string
     */
    public function getPaymentIdFromName($paymentName)
    {
        $sql = 'SELECT `id` FROM `s_core_paymentmeans` WHERE name = ?';
        return Shopware()->Db()->fetchOne($sql, $paymentName);
    }

    /**
     * Saves CRIF result in Address attributes
     * @param $addressID
     * @param $type - billing or shipping
     * @param $response
     */
    public function saveCRIFResultInAddress($addressID, $type, $response)
    {
        if (!$addressID) {
            return;
        }

        $address = $this->getCustomerAddressById($addressID, $type);
        if ($attribute = $address->getAttribute()) {
            $attribute->setFatchipctCrifdate(date('Y-m-d H:i:s'));
            $attribute->setFatchipctCrifdescription($response->getDescription());
            $attribute->setFatchipctCrifresult($response->getResult());
            $attribute->setFatchipctCrifstatus($response->getStatus());
            Shopware()->Models()->persist($attribute);
            Shopware()->Models()->flush();
        }
    }

    /**
     * checks if AmazonPay is enabled
     *
     * @return bool
     */
    public function isAmazonPayActive()
    {
        $payment = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->findOneBy(
            ['name' => 'fatchip_computop_amazonpay']
        );
        return $payment->getActive();
    }

    /**
     * checks if Papyal is enabled
     * ToDO refactor to generic method
     *
     * @return bool
     */
    public function isPaypalExpressActive()
    {
        $payment = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->findOneBy(
            ['name' => 'fatchip_computop_paypal_express']
        );
        return $payment->getActive();
    }

    /**
     * checks social security number should be collected for this order.
     * SSN is needed in DK, FI, SE and NO and not in other countries
     * @return bool
     */
    public function needSocialSecurityNumberForKlarna()
    {
        if ($countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'DK' || $countryIso == 'FI' || $countryIso == 'SE' || $countryIso == 'NO') {
                return true;
            }
        }

        return false;
    }

    /**
     * * Klarna is only available for Private persons in DE, AT and NL. So for companies we block Klarna in these countries
     * @param $userData
     * @return bool
     */
    public function isKlarnaBlocked($userData)
    {

        $countryIso = $this->getBillingIsoForCurrentOrder();

        if ($countryIso == 'DE' || $countryIso == 'AT' || $countryIso == 'NL') {
            if (!empty($userData['billingaddress']['company'])) {
                return true;
            }
        }

        return false;
    }


    /**
     * returns the label for the SSN field for klarna
     * For comapanies the label is Handelsregisternummer, for NO we add last 5 digits
     * @param $userData
     * @return string
     */
    public function getSocialSecurityNumberLabelForKlarna($userData)
    {
        $label = 'Sozialversicherungsnummer (letzte 4 Ziffern)';
        //For comapnies, the field is called Handelsregisternummer
        if (isset($userData['billingaddress']['company'])) {
            $label = '
            Handelsregisternummer';
        } else if ($countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'NO') {
                $label = 'Sozialversicherungsnummer (letzte 5 Ziffern)';
            }
        }

        return $label;
    }

    /**
     * Annual salary is mandatory for Private Customers in Denmark
     * @param $userData
     * @return bool
     */
    public function needAnnualSalaryForKlarna($userData)
    {
        if (!isset($userData['billingaddress']['company']) && $countryIso = $this->getBillingIsoForCurrentOrder()) {
            //only if billingcountry in DK, FI, SE, NO we show the social security number field
            if ($countryIso == 'DK') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the length for the SSN field - for NO its 5, for other countries 4. For companies there is no max length
     * @param $userData
     * @return int|null
     */
    public function getSSNLength($userData)
    {
        //for companies, we do not need a max length
        if (!isset($userData['billingaddress']['company']) && $countryIso = $this->getBillingIsoForCurrentOrder()) {
            if ($countryIso == 'NO') {
                return 5;
            }
            return 4;
        }
        return null;
    }

    /**
     * returns the ISO country code for the billing address of the current order
     * @return null|string
     */
    private function getBillingIsoForCurrentOrder()
    {
        if ($orderVars = Shopware()->Session()->sOrderVariables) {
            $userData = $orderVars['sUserData'];
            $countryID = $this->getCountryIdFromAddress($userData['billingaddress']);
        } else if ($user = Shopware()->Modules()->Admin()->sGetUserData()) {
            $countryID = $this->getCountryIdFromAddress($user['billingaddress']);
        }

        if ($countryID) {
            return $this->getCTCountryIso($countryID);
        }

        return null;

    }

    /**
     * Return whether or not a abo-article is in the basket
     *
     * @return bool
     * @throws \Exception
     */
    public function isAboCommerceArticleInBasket()
    {
        if (!$this->isAboCommerceActive()) {
            return false;
        }

        $entityManager = Shopware()->Models();
        $builder = $entityManager->createQueryBuilder();
        $builder->select($entityManager->getExpressionBuilder()->count('basket.id'))
            ->from('Shopware\Models\Order\Basket', 'basket')
            ->innerJoin('basket.attribute', 'attribute')
            ->where('basket.sessionId = :sessionId')
            ->andWhere('attribute.swagAboCommerceDeliveryInterval IS NOT NULL')
            ->setParameters(array('sessionId' => Shopware()->SessionID()));

        $count = $builder->getQuery()->getSingleScalarResult();

        return (bool)$count;
    }

    /**
     * check if abo commerce plugin is activated
     *
     * @return bool
     */
    protected function isAboCommerceActive()
    {
        $sql = "SELECT 1 FROM s_core_plugins WHERE name='SwagAboCommerce' AND active=1";

        $result = Shopware()->Db()->fetchOne($sql);
        if ($result != 1) {
            return false; //not installed
        }
        return true;
    }

    /**
     * Remove whitespaces from input string
     * @param $input String
     * @return string without whitespaces
     */
    public function removeWhitespaces($input)
    {
        return preg_replace('/\s+/', '', $input);
    }

    /** retrieve json config file from afterbuy
     * @param $merchantId String
     * @param $userData array
     * @return bool
     */
    public function afterpayProductExistsforBasketValue($merchantId, $userData, $fallback = true)
    {
        $countryCode = strtolower($userData['additional']['country']['countryiso']);
        $afterpayMerchantId = 'CP_'.$merchantId;
        $basket = Shopware()->Modules()->Basket()->sGetBasket();

        $handle = curl_init('https://cdn.myafterpay.com/config/'.$countryCode.'/'.$afterpayMerchantId.'json');
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if($httpCode == 404) {
            if (!$fallback) {
                return false;
            } else {
                $handle = curl_init('https://cdn.myafterpay.com/config/de/4564.json');
                curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
                /* Get the HTML or whatever is linked in $url. */
                $response = curl_exec($handle);
                curl_close($handle);
            }
        }
        $availableProducts = json_decode($response);
        $minBasketValues = array_column($availableProducts->availablePaymentMethods->installments, 'minAmount');
        $maxBasketValues = array_column($availableProducts->availablePaymentMethods->installments, 'maxAmount');

        $min = min($minBasketValues);
        $max = max($maxBasketValues);

        return ($basket['AmountNumeric'] >= $min && $basket['AmountNumeric'] <= $max);
    }

    /**
     * Sets the userData paramater for Computop calls to Shopware Version and Module Version
     *
     * @return string
     * @throws Exception
     */
    public function getUserDataParam()
    {
        return 'Shopware Version: ' . self::getShopwareVersion() . ', Modul Version: ' . Shopware()->Plugins()->Frontend()->FatchipCTPayment()->getVersion();
    }

    /**
     * Creates a CTOrder. When return value is not instanceof CTOrder, an error occured and the return value is an error
     * array:
     *  ['CTError' => [
     *      'CTErrorMessage',
     *      'CTErrorCode'
     *  ]]
     *
     * @param $userData
     *
     * @return array|CTOrder
     */
    public function createCTOrder($userData)
    {
        try {
            $basket = Shopware()->Modules()->Basket()->sGetBasket();
        } catch (Exception $e) {
            $ctError = [];
            $ctError['CTErrorMessage'] = 'Beim auslesen des Warenkorbs ist ein Fehler aufgetreten<BR>';
            $ctError['CTErrorCode'] = $e->getMessage();
            return ['CTError' => $ctError];
        }

        $ctOrder = new CTOrder();
        $ctOrder->setAmount($basket['AmountNumeric'] * 100);
        $ctOrder->setCurrency(Shopware()->Container()->get('currency')->getShortName());
        // try catch in case Address Splitter return exceptions
        try {
            $ctOrder->setBillingAddress($this->getCTAddress($userData['billingaddress']));
            $ctOrder->setShippingAddress($this->getCTAddress($userData['shippingaddress']));
        } catch (Exception $e) {
            $ctError = [];
            $ctError['CTErrorMessage'] = 'Bei der Verarbeitung Ihrer Adresse ist ein Fehler aufgetreten<BR>';
            $ctError['CTErrorCode'] = $e->getMessage();
            return ['CTError' => $ctError];
        }
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);
        $ctOrder->setOrderDesc(Shopware()->Config()->shopName);
        return $ctOrder;
    }

    /**
     * @param $userData
     * @return KlarnaPayments
     */
    public function createCTKlarnaPayment($userData)
    {
        // TODO: store payment as singleton?

        $paymentName = $userData['additional']['payment']['name'];

        $payTypes = [
            'pay_now' => 'pay_now',
            'pay_later' => 'pay_later',
            'slice_it' => 'pay_over_time'
        ];

        // set payType to correct value
        foreach ($payTypes as $key => $value) {
            $length = strlen($key);
            if (substr($paymentName, -$length) === $key) {
                $payType = $value;
                break;
            }
        }

        if (!isset($payType)) {
            return null;
        }

        $taxAmount = 0;
        $articleList = [];

        try {
            foreach (Shopware()->Modules()->Basket()->sGetBasket()['content'] as $item) {
                $itemTaxAmount = round(str_replace(',', '.', $item['tax']) * 100);
                $quantity = (int)$item['quantity'];
                $taxAmount += ($itemTaxAmount * $quantity);
                $articleList['order_lines'][] = [
                    'name' => $item['articlename'],
                    'quantity' => $quantity,
                    'unit_price' => round($item['priceNumeric'] * 100),
                    'total_amount' => round(str_replace(',', '.', $item['price']) * 100),
                    'tax_rate' => $item['tax_rate'] * 100,
                    'total_tax_amount' => $itemTaxAmount,
                ];
            }
        } catch (Exception $e) {
            $this->logger->error('Error occured, when calling sGetBasket()');

            return null;
        }
        $articleList = base64_encode(json_encode($articleList));

        $URLConfirm = Shopware()->Front()->Router()->assemble([
            'controller' => 'checkout',
            'action' => 'finish',
            'forceSecure' => true,
        ]);

        $ctOrder = $this->createCTOrder($userData);

        if (!$ctOrder instanceof CTOrder) {
            return null;
        }

        $container = Shopware()->Container();
        $pluginConfig = $container->get('plugins')->Frontend()->FatchipCTPayment()->Config()->toArray();
        $klarnaAccount = $pluginConfig['klarnaaccount'];

        /** @var KlarnaPayments $payment */
        $payment = $container->get('FatchipCTPaymentApiClient')->getPaymentClass('KlarnaPayments', $pluginConfig);
        $payment->storeKlarnaSessionRequestParams(
            $taxAmount,
            $articleList,
            $URLConfirm,
            $payType,
            $klarnaAccount, // TODO: get from plugin config
            $userData['additional']['country']['countryiso'],
            $ctOrder->getAmount(),
            $ctOrder->getCurrency(),
            $this->generateTransID(),
            $_SERVER['REMOTE_ADDR']
        );

        return $payment;
    }

    /**
     * @param int $digitCount Optional parameter for the length of resulting
     *                        transID. The default value is 12.
     *
     * @return string The transID with a length of $digitCount.
     */
    public function generateTransID($digitCount = 12)
    {
        mt_srand((double)microtime() * 1000000);

        $transID = (string)mt_rand();
        // y: 2 digits for year
        // m: 2 digits for month
        // d: 2 digits for day of month
        // H: 2 digits for hour
        // i: 2 digits for minute
        // s: 2 digits for second
        $transID .= date('ymdHis');
        // $transID = md5($transID);
        $transID = substr($transID, 0, $digitCount);

        return $transID;
    }

    public function selectDefaultPaymentAfterKlarna($userData)
    {
        $defaultPayment = Shopware()->Config()->get('defaultpayment');
        $modelManager = Shopware()->Models();
        $repo = $modelManager->getRepository(Customer::class);

        /** @var Customer $customer */
        $customer = $repo->find($userData['additional']['user']['id']);
        $customer->setPaymentId($defaultPayment);

        $modelManager->persist($customer);
        try {
            $modelManager->flush();
        } catch (OptimisticLockException $e) {
            $this->logger->error('Unable to store default payment after klarna', [
                'userID' => $userData['additional']['user']['id'],
                'paymentID' => $userData['additional']['user']['paymentID'],
                'defaultPayment' => $defaultPayment
            ]);
        }
    }
}
