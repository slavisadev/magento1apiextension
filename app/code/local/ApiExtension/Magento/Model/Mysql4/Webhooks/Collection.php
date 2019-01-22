<?php

class ApiExtension_Magento_Model_Mysql4_Webhooks_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * ApiExtension_Magento_Model_Mysql4_Webhooks_Collection
     */
    public function _construct()
    {
        $this->_init('apiExtension/webhooks');
    }
}
