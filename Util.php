<?php

/**
 * The First Cash Solution Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The First Cash Solution Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with First Cash Solution Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7 , 7.1
 *
 * @category  Payment
 * @package   First Cash Solution_Shopware5_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2018 First Cash Solution
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      https://www.firstcashsolution.de/
 */

namespace Shopware\Plugins\FatchipFCSPayment;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enlight_Controller_Action;
use Monolog\Handler\RotatingFileHandler;
use Exception;
use Fatchip\FCSPayment\CTAddress\CTAddress;
use Fatchip\FCSPayment\CTOrder\CTOrder;
use Shopware\Components\Logger;
use Shopware\Models\Customer\Customer;
use Shopware_Plugins_Frontend_FatchipFCSPayment_Bootstrap as FatchipFCSPayment;
use VIISON\AddressSplitter\AddressSplitter;
use Shopware;

require_once 'Components/Api/vendor/autoload.php';


/**
 * Class Util
 * @package Shopware\Plugins\FatchipFCSPayment\
 */
class Util
{
    /**
     * @var Logger
     */
    protected $logger;
    protected $container;
    /** @var FatchipFCSPayment $plugin */
    protected $plugin;
    /** @var [] */
    protected $pluginConfig;

    public function __construct()
    {
        $this->logger = new Logger('FatchipFCSPayment');
        $this->container = Shopware()->Container();
        $this->plugin = $this->container->get('plugins')->Frontend()->FatchipFCSPayment();
        $this->pluginConfig = $this->plugin->Config()->toArray();
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
        // to support addresses without house number only use the splitter when numbers are found in street
        if (preg_match('~[0-9]+~', $swAddress['street'])) {
            $splitAddress = AddressSplitter::splitAddress($swAddress['street']);
            $street = $splitAddress['streetName'];
            $housenr = $splitAddress['houseNumber'];
        } else {
            $street = $swAddress['street'];
            $housenr = '';
        }

        return new CTAddress(
            ($swAddress['salutation'] == 'mr') ? 'Herr' : 'Frau',
            $swAddress['company'],
            ($swAddress['firstname']) ? $swAddress['firstname'] : $swAddress['firstName'],
            ($swAddress['lastname']) ? $swAddress['lastname'] : $swAddress['lastName'],
            $street,
            $housenr,
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

        return $attribute->getFatchipfcsSocialsecuritynumber();
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

        return $attribute->getFatchipfcsAnnualSalary();
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
        return $attribute->getFatchipfcsLastschriftbank();
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
        return $attribute->getFatchipfcsLastschriftiban();
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
        return $attribute->getFatchipfcsAfterpayinstallmentiban();
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
        return $attribute->getFatchipfcsLastschriftaccowner();
    }

    /**
     * gets customer attribute
     * @param $user
     * @return mixed
     */
    public function getUserCreditcardInitialPaymentSuccess($user)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($user['additional']['user']['id']);
        $attribute = $user->getAttribute();

        if ($attribute === null) {
            return null;
        }
        return $attribute->getFatchipfcsCreditcardinitialpaymentsuccess();
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
        $attributes->setFatchipfcsSocialsecuritynumber($ssn);
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
        $attributes->setFatchipfcsAnnualSalary($ssn);
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
        $attributes->setFatchipfcsLastschriftbank($bank);
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
        $attributes->setFatchipfcsLastschriftiban($iban);
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
        $attributes->setFatchipfcsAfterpayinstallmentiban($iban);
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
        $attributes->setFatchipfcsLastschriftaccowner($kontoinhaber);
        Shopware()->Models()->persist($attributes);
        Shopware()->Models()->flush($attributes);

    }

    /**
     * updates user attributes
     * @param $userId
     * @param $success
     */
    public function updateUserCreditcardInitialPaymentSuccess($userId, $success)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $attributes = $user->getAttribute();
        $attributes->setFatchipfcsCreditcardinitialpaymentsuccess($success);
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
     * 'fatchip_firstcash_klarna_slice_it'
     *
     * @return array
     */
    public function getActivatedKlarnaPaymentTypes()
    {
        /** @noinspection SqlResolve */
        $sql = 'SELECT name FROM s_core_paymentmeans WHERE name like "%fatchip_firstcash_klarna%"';

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
        $paymentNamePrefix = 'fatchip_firstcash_klarna_';
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
            $attribute->setFatchipfcsCrifdate(date('Y-m-d H:i:s'));
            $attribute->setFatchipfcsCrifdescription($response->getDescription());
            $attribute->setFatchipfcsCrifresult($response->getResult());
            $attribute->setFatchipfcsCrifstatus($response->getStatus());
            Shopware()->Models()->persist($attribute);
            Shopware()->Models()->flush();
        }
    }

    /**
     * TODO: move to helper
     * checks if AmazonPay is enabled
     *
     * @return bool
     */
    public function isAmazonPayActive()
    {
        $payment = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->findOneBy(
            ['name' => 'fatchip_firstcash_amazonpay']
        );
        return $payment->getActive();
    }

    /**
     * checks if Papyal is enabled
     * ToDO move to helper
     *
     * @return bool
     */
    public function isPaypalExpressActive()
    {
        $payment = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment')->findOneBy(
            ['name' => 'fatchip_firstcash_paypal_express']
        );
        return $payment->getActive();
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
            ->setParameters(array('sessionId' => Shopware()->Session()->offsetGet('sessionId')));
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

    /**
     * TODO: move to afterpay helper
     * retrieve json config file from afterbuy
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
     * Sets the userData paramater for First Cash Solution calls to Shopware Version and Module Version
     *
     * @return string
     * @throws Exception
     */
    public function getUserDataParam()
    {
        return 'Shopware Version: ' . self::getShopwareVersion() . ', Modul Version: ' . Shopware()->Plugins()->Frontend()->FatchipFCSPayment()->getVersion();
    }

    /**
     * Creates a CTOrder. When return value is not instanceof CTOrder, an error occured and the return value is an error
     * array:
     *  ['CTError' => [
     *      'CTErrorMessage',
     *      'CTErrorCode'
     *  ]]
     *
     * @return array|CTOrder
     */
    public function createCTOrder()
    {
        $userData = Shopware()->Modules()->Admin()->sGetUserData();

        try {
            $basket = Shopware()->Modules()->Basket()->sGetBasket();
        } catch (Exception $e) {
            $ctError = [];
            $ctError['CTErrorMessage'] = Shopware()->Snippets()
                ->getNamespace('frontend/FatchipFCSPayment/translations')
                ->get('errorBasket');
            $ctError['CTErrorCode'] = $e->getMessage();
            return ['FCSError' => $ctError];
        }

        $ctOrder = new CTOrder();
        $ctOrder->setAmount(($basket['AmountNumeric'] + $this->calculateShippingCosts()) * 100);
        $ctOrder->setCurrency(Shopware()->Container()->get('currency')->getShortName());
        // try catch in case Address Splitter return exceptions
        try {
            $ctOrder->setBillingAddress($this->getCTAddress($userData['billingaddress']));
            $ctOrder->setShippingAddress($this->getCTAddress($userData['shippingaddress']));
        } catch (Exception $e) {
            $ctError = [];
            $ctError['CTErrorMessage'] = Shopware()->Snippets()
                ->getNamespace('frontend/FatchipFCSPayment/translations')
                ->get('errorAddress');
            $ctError['CTErrorCode'] = $e->getMessage();
            return ['FCSError' => $ctError];
        }
        $ctOrder->setEmail($userData['additional']['user']['email']);
        $ctOrder->setCustomerID($userData['additional']['user']['id']);
        $ctOrder->setOrderDesc(Shopware()->Config()->shopName);
        return $ctOrder;
    }

    /**
     * Selects the store's default payment as default payment for the user.
     */
    public function selectDefaultPayment()
    {
        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        $defaultPayment = Shopware()->Config()->get('defaultpayment');
        $modelManager = Shopware()->Models();
        $repo = $modelManager->getRepository(Customer::class);

        /** @var Customer $customer */
        $customer = $repo->find($userData['additional']['user']['id']);
        $customer->setPaymentId($defaultPayment);

        try {
            $modelManager->persist($customer);
            $modelManager->flush();
        } catch (OptimisticLockException $e) {
            $this->logger->error('Unable to select default payment', [
                'userID' => $userData['additional']['user']['id'],
                'paymentID' => $userData['additional']['user']['paymentID'],
                'defaultPayment' => $defaultPayment
            ]);
        } catch (ORMException $e) {
        }
    }

    public function calculateShippingCosts()
    {
        $shippingCosts = Shopware()->Modules()->Admin()->sGetPremiumShippingcosts();

        return $shippingCosts['brutto'];
    }

    /**
     * @param Enlight_Controller_Action $controller
     * @param string $errMsg
     */
    public function redirectToShippingPayment($controller, $errMsg)
    {
        // redirect to shipping payment with error message
        $session = Shopware()->Session();

        $ctError = [];
        $ctError['CTErrorMessage'] = $errMsg;
        $ctError['CTErrorCode'] = '';

        $session->offsetSet('FCSError', $ctError);

        try {
            $controller->redirect([
                'action' => 'shippingPayment',
                'controller' => 'checkout'
            ]);
        } catch (Exception $e) {
            // TODO: log
        }
    }

    /**
     * Remove payment instance
     *
     * @param string $paymentName
     *
     */
    public function removePayment($paymentName)
    {
        $payment = $this->Payments()->findOneBy(
            array(
                'name' => $paymentName
            )
        );
        if ($payment === null) {
            // do nothing

        } else {
            try {
                Shopware()->Models()->remove($payment);
                Shopware()->Models()->flush();
            } catch (ORMException $e) {
            }
        }
    }

    public function hidePayment($name, $payments) {
        if (empty($payments)) {
            return $payments;
        }
        $paymentIndexes = array_combine(array_column($payments, 'name'), array_keys($payments));

        if(array_key_exists($name, $paymentIndexes)) {
            unset($payments[$paymentIndexes[$name]]);
        }

        return $payments;
    }

    /**
     * Returns the Remote IP supporting
     * load balancer and proxy setups
     *
     * @return string
     */
    public static function getRemoteAddress()
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'];
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $proxy = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (!empty($proxy)) {
                $proxyIps = explode(',', $proxy);
                $relevantIp = array_shift($proxyIps);
                $relevantIp = trim($relevantIp);
                if (!empty($relevantIp)) {
                    return $relevantIp;
                }
            }
        }
        // Cloudflare sends a special Proxy Header, see:
        // https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-Cloudflare-handle-HTTP-Request-headers-
        // In theory, CF should respect X-Forwarded-For, but in some instances this failed
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        return $remoteAddr;
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function log($message, array $context) {
        $logPath = Shopware()->DocPath();
        if (Util::isShopwareVersionGreaterThanOrEqual('5.1')) {
            $logFile = $logPath . 'var/log/FatchipFCSPaymentExtended_production.log';
        } else {
            $logFile = $logPath . 'logs/FatchipFCSPaymentExtended_production.log';
        }
        $rfh = new RotatingFileHandler($logFile, 14);
        $logger = new Logger('FatchipFCSPayment');
        $logger->pushHandler($rfh);
        $logger->error($message, $context);
    }
}
