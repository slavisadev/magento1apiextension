<?php

class ApiExtension_Magento_Model_Api2_Storeview_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_Storeview
{
    /**
     * Gets list of store views
     */
    public function _retrieveCollection()
    {
        $result = array();

        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $result[] = array(
                        'id' => $store->getId(),
                        'code' => $store->code,
                        'name' => $store->name,
                        'website_id' => $website->website_id,
                        'store_group_id' => $group->group_id,
                    );
                }
            }
        }

        return $result;
    }

}
