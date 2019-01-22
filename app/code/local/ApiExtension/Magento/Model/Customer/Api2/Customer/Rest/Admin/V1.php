<?php

class ApiExtension_Magento_Model_Customer_Api2_Customer_Rest_Admin_V1 extends Mage_Customer_Model_Api2_Customer_Rest_Admin_V1
{
    /**
     * Get customers list
     *
     * <entity_id>2</entity_id>
     * <firstname>John</firstname>
     * <lastname>Doe</lastname>
     * <city>PA</city>
     * <region>Palau</region>
     * <postcode>19103</postcode>
     * <country_id>US</country_id>
     * <telephone>610-634-1181</telephone>
     * <prefix>Dr.</prefix>
     * <middlename></middlename>
     * <suffix>Jr.</suffix>
     * <company></company>
     * <fax></fax>
     * <vat_id>123456789</vat_id>
     * <street>
     * <data_item>2356 Jody Road Philadelphia</data_item>
     * <data_item>844 Jefferson Street; 4510 Willis Avenue</data_item>
     * </street>
     * <is_default_billing>1</is_default_billing>
     * <is_default_shipping>1</is_default_shipping>
     *
     * @return array
     */
    public function _retrieveCollection()
    {
        $data = $this->_getCollectionForRetrieve()->load()->toArray();

        $counter = 0;
        $customers = array();
        foreach ($data as $item) {

            /* @var $address Mage_Customer_Model_Address */
            $customer = Mage::getModel('customer/customer')->load($item['entity_id']);
            $customers[$counter] = $item;

            foreach ($customer->getAddresses() as $address) {
                $customers[$counter]['addresses'][] = array(
                    'city' => $address->getCity(),
                    'firstname' => $address->getFirstname(),
                    'lastname' => $address->getLastname(),
                    'region' => $address->getRegion(),
                    'postcode' => $address->getPostcode(),
                    'country_id' => $address->getCountry(),
                    'telephone' => $address->getTelephone(),
                    'prefix' => $address->getPrefix(),
                    'middlename' => $address->getMiddlename(),
                    'fax' => $address->getFax(),
                    'vat_id' => $address->getVatId(),
                    'is_default_billing' => $this->isBillingAddress($customer, $address),
                    'is_default_shipping' => $this->isShippingAddress($customer, $address),
                    'street' => $address->getStreet1() . ' ' . $address->getStreet2(),
                );
            }

            $counter++;
        }

        return $customers;
    }

    /**
     * @param $customer
     * @param $address
     *
     * @return int
     */
    public function isBillingAddress($customer, $address)
    {
        $defaultBilling = $customer->getDefaultBillingAddress();
        if ($defaultBilling) {
            if ($defaultBilling->getId() == $address->getId()) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    /**
     * @param $customer
     * @param $address
     *
     * @return int
     */
    public function isShippingAddress($customer, $address)
    {
        $defaultShipping = $customer->getDefaultShippingAddress();
        if ($defaultShipping) {
            if ($defaultShipping->getId() == $address->getId()) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    /**
     * Create customer
     *
     * @param array $data
     * @return string
     */
    public function _create(array $data)
    {
        if ($this->checkCustomerExists($data)) {
            return $this->_update($data);
        }

        return parent::_create($data);
    }

    /**
     * Update Customer
     *
     * @param array $data
     * @return string
     */
    public function _update(array $data)
    {
        /** @var $customer Mage_Customer_Model_Customer */
        $requestId = $this->getRequest()->getParam('id');

        if (isset($requestId)) {
            $customer = $this->_loadCustomerById($requestId);
        } else {
            $customerId = $this->getCustomerIdByEmail($data['email']);
            $customer = $this->_loadCustomerById($customerId);
        }

        /** @var $validator Mage_Api2_Model_Resource_Validator_Eav */
        $validator = Mage::getResourceModel('api2/validator_eav', array('resource' => $this));

        unset($data['email']);
        unset($data['website_id']);

        $data = $validator->filter($data);

        if (!$validator->isValidData($data, true)) {
            foreach ($validator->getErrors() as $error) {
                $this->_error($error, Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
            }
            $this->_critical(self::RESOURCE_DATA_PRE_VALIDATION_ERROR);
        }
        $customer->addData($data);

        try {
            $customer->save();

        } catch (Mage_Core_Exception $e) {
            $this->_error($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }

        return $this->_getLocation($customer);
    }

    /**
     * @param array $data
     * @return bool|Mage_Customer_Model_Customer
     */
    public function checkCustomerExists(array $data)
    {
        $customer = Mage::getModel('customer/customer')
            ->setWebsiteId($data['website_id'])
            ->loadByEmail($data['email']);

        if ($customer->getId()) {
            return true;
        }

        return false;
    }

    /**
     * @param $email
     * @return mixed|null
     */
    public function getCustomerIdByEmail($email)
    {
        foreach (Mage::app()->getWebsites() as $website) {
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId($website->getId());

            $customer->loadByEmail($email);

            if ($customer->getId()) {
                return $customer->getId();
            }
        }
        return null;
    }
}
