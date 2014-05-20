<?php

/**
 * Class Laposta_Connect_Helper_Customer
 *
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property string $password_hash
 * @property string $default_billing
 * @property string $default_shipping
 * @property string $created_in
 * @property string $store_id
 * @property string $group_id
 * @property string $website_id
 * @property string $prefix
 * @property string $middlename
 * @property string $suffix
 * @property string $dob
 * @property string $taxvat
 * @property string $confirmation
 * @property string $gender
 * @property string $created_at
 */
class Laposta_Connect_Helper_Customer extends Mage_Core_Helper_Abstract
{
    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $customer;

    /**
     * @var array
     */
    protected $methodMapGet = array(
        'billing_address'  => 'getBillingAddress',
        'shipping_address' => 'getShippingAddress',
        'gender'           => 'getGender',
        'date_of_purchase' => 'getLastPurchaseDate',
        'telephone'        => 'getTelephone',
        'company'          => 'getCompany',
        'group_name'       => 'getCustomerGroupName',
    );

    /**
     * @var array
     */
    protected $methodMapSet = array(
        'gender'           => 'setGender',
        'telephone'        => 'setTelephone',
        'company'          => 'setCompany',
    );

    /**
     * @var array
     */
    protected $attributeMap = array();

    /**
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return $this
     */
    public function setCustomer(Mage_Customer_Model_Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Magic getter hook to get() method.
     *
     * @param string $name
     *
     * @return mixed
     * @throws RuntimeException
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Magic setter hook to set() method.
     *
     * @param string $name
     * @param string $value
     *
     * @return mixed
     */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * Resolve a list of properties.
     *
     * @param array $list
     *
     * @return array
     */
    public function resolve(array $list = array())
    {
        $result = array();

        foreach ($list as $property) {
            $result[$property] = $this->get($property);
        }

        return $result;
    }

    /**
     * Resolve a data value for the customer.
     *
     * @param string $name
     *
     * @return mixed
     * @throws RuntimeException
     */
    public function get($name)
    {
        if (!$this->customer instanceof Mage_Customer_Model_Customer) {
            throw new RuntimeException("Customer has not been set. Unable to resolve value for '$name'");
        }

        if (!isset($this->methodMapGet[$name])) {
            return $this->getCustomerAttribute($name);
        }

        $method = $this->methodMapGet[$name];

        return $this->$method();
    }

    /**
     * Set a data value for the customer
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     * @throws RuntimeException
     */
    public function set($name, $value)
    {
        if (!$this->customer instanceof Mage_Customer_Model_Customer) {
            throw new RuntimeException("Customer has not been set. Unable to set value for '$name'");
        }

        if (!isset($this->methodMapSet[$name])) {
            return $this->setCustomerAttribute($name, $value);
        }

        $method = $this->methodMapSet[$name];

        $this->$method($value);

        return $this;
    }

    /**
     * Get a customer attribute by name
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getCustomerAttribute($name)
    {
        if (isset($this->attributeMap[$name])) {
            $name = $this->attributeMap[$name];
        }

        $value = $this->getCustomer()->getData($name);

        return $value;
    }

    /**
     * Set a customer attribute by name
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    protected function setCustomerAttribute($name, $value)
    {
        if (isset($this->attributeMap[$name])) {
            $name = $this->attributeMap[$name];
        }

        $this->getCustomer()->setData($name, $value);

        return $this;
    }

    /**
     * Get the billing address
     *
     * @return string
     */
    protected function getBillingAddress()
    {
        return $this->formatAddress($this->getCustomer()->getPrimaryBillingAddress());
    }

    /**
     * Get the shipping address
     *
     * @return string
     */
    protected function getShippingAddress()
    {
        return $this->formatAddress($this->getCustomer()->getPrimaryShippingAddress());
    }

    /**
     * Created a formatted address string
     *
     * @param mixed $address
     *
     * @return string
     */
    protected function formatAddress($address)
    {
        if (!$address instanceof Mage_Customer_Model_Address) {
            return '';
        }

        $street   = $address->getStreet(-1);
        $city     = $address->getData('city');
        $region   = $address->getRegion();
        $postcode = $address->getData('postcode');
        $country  = $address->getCountryModel()->getName();

        return trim(implode("\n", array($street, $postcode . ' ' . $city, $region, $country)));
    }

    /**
     * Get the textual customer gender
     *
     * @return string
     */
    protected function getGender()
    {
        $genderId = $this->getCustomerAttribute('gender');
        $gender   = $this->getCustomer()->getAttribute('gender')->getSource()->getOptionText($genderId);

        if ($gender === false) {
            $gender = '';
        }

        return $gender;
    }

    /**
     * Set the textual customer gender
     *
     * @return string
     */
    protected function setGender($value)
    {
        $options  = $this->getCustomer()->getAttribute('gender')->getSource()->getAllOptions(false);
        $genderId = '';

        foreach ($options as $option) {
            if ($option['label'] != $value) {
                continue;
            }

            $genderId = $option['value'];

            break;
        }

        $this->setCustomerAttribute('gender', $genderId);

        return $this;
    }

    /**
     * Get the date of the customers last order
     *
     * @return string
     */
    protected function getLastPurchaseDate()
    {
        $orders = Mage::getResourceModel('sales/order_collection')->addFieldToSelect('*')->addFieldToFilter(
            'customer_id',
            $this->getCustomer()->getId()
        )->addAttributeToSort('created_at', 'DESC')->setPageSize(1);

        if ($orders->count() === 0) {
            return '';
        }

        return $orders->getFirstItem()->getData('created_at');
    }

    /**
     * Get the customer telephone number
     *
     * @return string
     */
    protected function getTelephone()
    {
        $address = $this->getCustomer()->getPrimaryBillingAddress();

        if (!$address instanceof Mage_Customer_Model_Address) {
            return '';
        }

        return $address->getData('telephone');
    }

    /**
     * Set the customer telephone number
     *
     * @return string
     */
    protected function setTelephone($value)
    {
        $address = $this->getCustomer()->getPrimaryBillingAddress();

        if (!$address instanceof Mage_Customer_Model_Address) {
            return '';
        }

        return $address->setData('telephone', $value);
    }

    /**
     * Get the customer company name
     *
     * @return string
     */
    protected function getCompany()
    {
        $address = $this->getCustomer()->getPrimaryBillingAddress();

        if (!$address instanceof Mage_Customer_Model_Address) {
            return '';
        }

        return $address->getData('company');
    }

    /**
     * Set the customer company name
     *
     * @return string
     */
    protected function setCompany($value)
    {
        $address = $this->getCustomer()->getPrimaryBillingAddress();

        if (!$address instanceof Mage_Customer_Model_Address) {
            return '';
        }

        return $address->setData('company', $value);
    }

    /**
     * Get the name that corresponds thte customer group id.
     *
     * @return string
     */
    protected function getCustomerGroupName()
    {
        $groupId = $this->getCustomerAttribute('group_id');
        $group   = Mage::getModel('customer/group')->load($groupId);

        return $group->getData('customer_group_code');
    }
}
