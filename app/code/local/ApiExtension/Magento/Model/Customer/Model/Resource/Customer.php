<?php

/**
 * Class ApiExtension_Magento_Model_Customer_Model_Resource_Customer
 */
class ApiExtension_Magento_Model_Customer_Model_Resource_Customer extends Mage_Customer_Model_Resource_Customer {

    /**
     * @param Varien_Object $customer
     *
     * @return $this
     *
     * @throws Mage_Core_Exception
     */
    protected function _beforeSave(Varien_Object $customer)
    {
        Mage_Eav_Model_Entity_Abstract::_beforeSave($customer);

        if (!$customer->getEmail()) {
            throw Mage::exception('Mage_Customer', Mage::helper('customer')->__('Customer email is required'));
        }

        $adapter = $this->_getWriteAdapter();
        $bind    = array('email' => $customer->getEmail());

        $select = $adapter->select()
            ->from($this->getEntityTable(), array($this->getEntityIdField()))
            ->where('email = :email');
        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }
        if ($customer->getId()) {
            $bind['entity_id'] = (int)$customer->getId();
            $select->where('entity_id != :entity_id');
        }

        // set confirmation key logic
        if ($customer->getForceConfirmed()) {
            $customer->setConfirmation(null);
        } elseif (!$customer->getId() && $customer->isConfirmationRequired()) {
            $customer->setConfirmation($customer->getRandomConfirmationKey());
        }
        // remove customer confirmation key from database, if empty
        if (!$customer->getConfirmation()) {
            $customer->setConfirmation(null);
        }

        return $this;
    }
}
