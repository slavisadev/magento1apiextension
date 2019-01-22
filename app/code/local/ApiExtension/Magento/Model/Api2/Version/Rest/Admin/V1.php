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
class ApiExtension_Magento_Model_Api2_Version_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_Version
{
    /**
     * Gets current version of module and Magento
     */
    public function _retrieveCollection()
    {
        $moduleName = ApiExtension_Magento_Helper_Data::MODULE_NAME;

        return array(
            'magento_version' => Mage::getVersion(),
            'module_version' => (string) Mage::getConfig()->getNode()->modules->$moduleName->version,
        );
    }
}
