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
class ApiExtension_Magento_Model_Api2_Reindexers_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_Reindexers
{

    /**
     * Runs reindexer
     *
     * @param array $data
     *
     * @return string
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _create(array $data)
    {
        $indexingProcesses = Mage::getSingleton('index/indexer')->getProcessesCollection();

        foreach ($indexingProcesses as $process) {
            $process->reindexEverything();
        }
    }
}
