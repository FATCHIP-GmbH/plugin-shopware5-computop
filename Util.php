<?php
/**
 * Created by PhpStorm.
 * User: stefstar
 * Date: 1/12/18
 * Time: 7:28 PM
 */

namespace Shopware\FatchipCTPayment;

use Fatchip\CTPayment\CTAddress\CTAddress;
use VIISON\AddressSplitter\AddressSplitter;
use Shopware;


class Util
{

    /**
     * Type mapping from Shopware 5.2 to improve legacy compatibility,
     * as engine/Shopware/Bundle/AttributeBundle/Service/TypeMapping.php
     * is not present in Shopware versions < 5.2.0
     */
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_HTML = 'html';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_COMBOBOX = 'combobox';
    const TYPE_SINGLE_SELECTION = 'single_selection';
    const TYPE_MULTI_SELECTION = 'multi_selection';

    /**
     * @var array
     */
    private $types = [
      self::TYPE_STRING   => [
        'sql' => 'VARCHAR(500)',
        'dbal' => 'string',
        'allowDefaultValue' => true,
        'quoteDefaultValue' => true,
        'elastic' => ['type' => 'string']
      ],
      self::TYPE_TEXT     => [
        'sql' => 'TEXT',
        'dbal' => 'text',
        'allowDefaultValue' => false,
        'quoteDefaultValue' => false,
        'elastic' => ['type' => 'string']
      ],
      self::TYPE_HTML     => [
        'sql' => 'MEDIUMTEXT',
        'dbal' => 'text',
        'allowDefaultValue' => false,
        'quoteDefaultValue' => false,
        'elastic' => ['type' => 'string']
      ],
      self::TYPE_INTEGER  => [
        'sql' => 'INT(11)',
        'dbal' => 'integer',
        'allowDefaultValue' => true,
        'quoteDefaultValue' => false,
        'elastic' => ['type' => 'long']
      ],
      self::TYPE_FLOAT    => [
        'sql' => 'DOUBLE',
        'dbal' => 'float',
        'allowDefaultValue' => true,
        'quoteDefaultValue' => false,
        'elastic' => ['type' => 'double']
      ],
      self::TYPE_BOOLEAN  => [
        'sql' => 'INT(1)',
        'dbal' => 'boolean',
        'allowDefaultValue' => true,
        'quoteDefaultValue' => false,
        'elastic' => ['type' => 'boolean']
      ],
      self::TYPE_DATE     => [
        'sql' => 'DATE',
        'dbal' => 'date',
        'allowDefaultValue' => true,
        'quoteDefaultValue' => true,
        'elastic' => ['type' => 'date', 'format' => 'yyyy-MM-dd']
      ],
      self::TYPE_DATETIME => [
        'sql' => 'DATETIME',
        'dbal' => 'datetime',
        'allowDefaultValue' => true,
        'quoteDefaultValue' => true,
        'elastic' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss']
      ],
      self::TYPE_COMBOBOX => [
        'sql' => 'MEDIUMTEXT',
        'dbal' => 'text',
        'allowDefaultValue' => false,
        'quoteDefaultValue' => false,
        'elastic' => ['type' => 'string']
      ],
      self::TYPE_SINGLE_SELECTION => [
        'sql' => 'VARCHAR(500)',
        'dbal' => 'text',
        'allowDefaultValue' => true,
        'quoteDefaultValue' => true,
        'elastic' => ['type' => 'string']
      ],
      self::TYPE_MULTI_SELECTION => [
        'sql' => 'MEDIUMTEXT',
        'dbal' => 'text',
        'allowDefaultValue' => false,
        'quoteDefaultValue' => false,
        'elastic' => ['type' => 'string']
      ]
    ];


    /**
     * @param array $swAddress
     * @return CTAddress
     * @throws Exception
     */
    public function getCTAddress(array $swAddress)
    {
        $splitAddress = AddressSplitter::splitAddress($swAddress['street']);

        return new CTAddress(
            ($swAddress['salutation'] == 'mr') ? 'Herr' : 'Frau',
            $swAddress['company'],
            $swAddress['firstname'],
            $swAddress['lastname'],
            $splitAddress['streetName'],
            $splitAddress['houseNumber'],
            $swAddress['zipcode'],
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

    public function getCTCountryIso3($countryId)
    {
        $countrySql = 'SELECT iso3 FROM s_core_countries WHERE id=?';
        return Shopware()->Db()->fetchOne($countrySql, [$countryId]);
    }


    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    public function getUserCustomerNumber($user)
    {
        $customerNumber = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $customerNumber = $user['billing']['customernumber'];
        } else {
            $customerNumber =$user['billingaddress']['customernumber'];
        }
        return $customerNumber;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    public function getUserDoB($user)
    {
        $birthdate = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $birthdate = $user['billing']['birthday'];
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
    public function getUserPhone($user)
    {
        return $user['billingaddress']['phone'];
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    public function getCountryIdFromAddress($swAddress)
    {
        $countryId = null;
        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
            $countryId = $swAddress['countryId'];
        } else {
            $countryId = $swAddress['countryID'];
        }
        return $countryId;
    }

    // SW 5.0 - 5.3 Compatibility
    // 5.0 - check
    // 5.1 - check
    // 5.2 - check
    // 5.3 - check
    public function updateUserDoB($userId, $birthday)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        if (Shopware::VERSION === '___VERSION___' || version_compare(Shopware::VERSION, '5.2.0', '>=')) {
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
    public function updateUserPhone($userId, $phone)
    {
        $user = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')->find($userId);

        $billing = $user->getBilling();
        $billing->setPhone($phone);
        Shopware()->Models()->persist($billing);
        Shopware()->Models()->flush($billing);
    }


    /**
     * @param $id
     * @param $type
     * @return null|object
     */
    public function getCustomerAddressById($id, $type) {
        if (version_compare(\Shopware::VERSION, '5.2.0', '<')) {
            $address = $type == 'shipping' ? $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Shipping')->find($id) :
              $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Billing')->find($id);
        } else {
            $address = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address')->find($id);
        }
        return $address;
    }

    /**
     * returns payment name
     *
     * @param string $paymentID
     * @return string
     */
    public function getPaymentNameFromId($paymentID)
    {
        $sql         = 'SELECT `name` FROM `s_core_paymentmeans` WHERE id = ?';
        return  Shopware()->Db()->fetchOne($sql, $paymentID);
    }





    /**
     * - returns the definition for attribute table extensions
     * - intended to be used with Shopware version >= 5.2.0
     * - Shopware versions < 5.2.0 can use the definitions by mapping
     * the types with unifiedToSQL() of this helper class
     *
     * @param int $pluginId
     * @return array
     */
    public function fcComputopAttributeExtensionsArray($pluginId)
    {
        return [
            's_user_attributes' => [
                'crif_result'              => 'string',
                'crif_date'                => 'date',
                'crif_status'               => 'string',
                'crif_description'               => 'string',
            ],
            's_user_billingaddress_attributes' => [
                'crif_result'              => 'string',
                'crif_date'                => 'date',
                'crif_status'               => 'string',
                'crif_description'               => 'string',
            ],
            's_user_shippingaddress_attributes' => [
                'crif_result'              => 'string',
                'crif_date'                => 'date',
                'crif_status'               => 'string',
                'crif_description'         => 'string',
            ]
        ];
    }

    /**
     * - returns the definition for attribute table extensions
     * - intended to be used with Shopware version >= 5.2.0
     *
     * @return array
     */
    public function fcComputopAttributeExtensionsArray52()
    {
        return [
            's_user_addresses_attributes' => [
                'crif_result'              => 'string',
                'crif_date'                => 'date',
                'crif_status'              => 'string',
                'crif_description'         => 'string',
            ]
        ];
    }

    /**
     * returns mapped SQL type from unified type string
     *
     * Type mapping from Shopware 5.2 to improve legacy compatibility,
     * as engine/Shopware/Bundle/AttributeBundle/Service/TypeMapping.php
     * is not present in Shopware versions < 5.2.0
     *
     * @param string $type
     * @return string
     */
    public function unifiedToSQL($type)
    {
        $type = strtolower($type);
        if (!isset($this->types[$type])) {
            return $this->types['string']['sql'];
        }
        $mapping = $this->types[$type];
        return $mapping['sql'];
    }

    /***
     * @param $addressID
     * @param $type - billing or shipping
     * @param $response
     */
    public function saveCRIFResultInAddress($addressID, $type, $response) {
        if (!$addressID) {
            return;
        }

        $address = $this->getCustomerAddressById($addressID, $type);
        if ($attribute = $address->getAttribute()) {
            $attribute->setFatchipcComputopCrifDate(date('Y-m-d H:i:s'));
            $attribute->setFatchipcComputopCrifDescription($response->getDescription());
            $attribute->setFatchipcComputopCrifResult($response->getResult());
            $attribute->setFatchipcComputopCrifStatus($response->getStatus());
            Shopware()->Models()->persist($attribute);
            Shopware()->Models()->flush();
        }

    }


}


