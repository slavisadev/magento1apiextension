<?php

class ApiExtension_Magento_Model_Mysql4_Webhooks extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * ApiExtension_Magento_Model_Mysql4_Webhooks
     */
    protected function _construct()
    {
        $this->_init('apiExtension/webhooks', 'id');
    }
}
