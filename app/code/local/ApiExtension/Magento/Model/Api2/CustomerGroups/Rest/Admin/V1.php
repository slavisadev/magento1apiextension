<?php

/**
 * API2 Abstract Resource methods:
 *
 * method string _create() _create(array $filteredData) creation of an entity
 * method void _multiCreate() _multiCreate(array $filteredData) processing and creation of a collection
 * method array _retrieve() retrieving an entity
 * method array _retrieveCollection() retrieving a collection
 * method void _update() _update(array $filteredData) update of an entity
 * method void _multiUpdate() _multiUpdate(array $filteredData) update of a collection
 * method void _delete() deletion of an entity
 * method void _multidelete() _multidelete(array $requestData) deletion of a collection
 */
class ApiExtension_Magento_Model_Api2_CustomerGroups_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_CustomerGroups
{
    /**
     * Gets customer group by ID
     */
    public function _retrieve()
    {
        /** @var Mage_Customer_Model_Group $customerGroup */
        $customerGroup = Mage::getModel('customer/group')->load($this->getRequest()->getParam('id'));

        if ($customerGroup->getId() === null) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return $customerGroup;
    }

    /**
     * Gets list of customer groups
     */
    public function _retrieveCollection()
    {
        $result = array();
        $collection = Mage::getModel('customer/group')
            ->getCollection();

        $this->_applyCollectionModifiers($collection);

        foreach ($collection as $customerGroup) {
            $result[] = $customerGroup->getData();
        }

        return $result;
    }

    /**
     * Creates new customer group
     *
     * @param array $data
     * @return string
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _create(array $data)
    {

        /** @var Mage_Customer_Model_Group $customerGroup */
        $customerGroup = Mage::getModel('customer/group');

        $customerGroup->setCustomerGroupCode($data['customer_group_code']);
        $customerGroup->setTaxClassId($data['tax_class_id']);

        try {
            $customerGroup->save();
        } catch (Mage_Core_Exception $e) {
            $this->_error($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }

        return $this->_getLocation($customerGroup);
    }

    /**
     * Updates customer group by ID
     *
     * @param array $data
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _update(array $data)
    {
        /** @var Mage_Customer_Model_Group $customerGroup */
        $customerGroup = Mage::getModel('customer/group')->load($this->getRequest()->getParam('id'));

        if ($customerGroup->getId() === null) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        $customerGroup->setCustomerGroupCode($data['customer_group_code']);
        $customerGroup->setTaxClassId($data['tax_class_id']);

        try {
            $customerGroup->save();
        } catch (Mage_Core_Exception $e) {
            $this->_error($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
    }

    /**
     * Deletes customer group
     */
    public function _delete()
    {
        /** @var Mage_Customer_Model_Group $customerGroup */
        $customerGroup = Mage::getModel('customer/group')->load($this->getRequest()->getParam('id'));

        if ($customerGroup->getId() === null) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        try {
            $customerGroup->delete();
        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
    }
}
