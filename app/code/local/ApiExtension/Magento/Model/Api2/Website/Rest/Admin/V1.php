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
class ApiExtension_Magento_Model_Api2_Website_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_Website
{
    /**
     * Gets list of websites
     */
    public function _retrieveCollection()
    {
        $result = array();

        foreach ($websites = Mage::getResourceModel('core/website_collection') as $website) {
            $result[] = array(
                'website_id' => $website->getId(),
                'name' => $website->name,
                'code' => $website->code,
                'is_default' => $website->is_default,
            );
        }

        return $result;
    }

}
