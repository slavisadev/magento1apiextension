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
class ApiExtension_Magento_Model_Api2_ProductAttributeSet_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_ProductAttributeSet
{
    /**
     * Gets attributes by attribute set ID
     */
    public function _retrieve()
    {
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($this->getRequest()->getParam('id'));

        if ($attributeSet->getId() === null) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return $attributeSet;
    }

    /**
     * Gets attributes by attribute set ID
     */
    public function _retrieveCollection()
    {
        $result = array();
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')->load();
        $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();

        foreach ($attributeSetCollection as $attributeSet) {
            if ($entityType != $attributeSet->getEntityTypeId()) {
                continue;
            }

            $result[] = array(
                'attribute_set_id' => $attributeSet->getAttributeSetId(),
                'entity_type_id' => $attributeSet->getEntityTypeId(),
                'attribute_set_name' => $attributeSet->getAttributeSetName(),
                'sort_order' => $attributeSet->getSortOrder(),
            );
        }

        return $result;
    }
}
