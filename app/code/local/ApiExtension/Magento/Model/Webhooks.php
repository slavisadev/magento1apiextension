<?php

class ApiExtension_Magento_Model_Webhooks extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init("apiExtension/webhooks");
    }
}
