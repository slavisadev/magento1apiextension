<?php

class ApiExtension_Magento_Block_Adminhtml_Webhooks extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * ApiExtension_Magento_Block_Adminhtml_Webhooks constructor.
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_webhooks';
        $this->_blockGroup = 'apiExtension';
        $this->_headerText = Mage::helper('apiExtension')->__('Webhooks Manager');
        $this->_addButtonLabel = Mage::helper('apiExtension')->__('Add New Webhook');

        parent::__construct();
    }

}
