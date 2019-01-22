<?php

class ApiExtension_Magento_Block_Adminhtml_Webhooks_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * @var ApiExtension_Magento_Helper_Data
     */
    private $helper;

    /**
     * ApiExtension_Magento_Block_Adminhtml_Webhooks_Edit constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'apiExtension';
        $this->_controller = 'adminhtml_webhooks';
        $this->helper = Mage::helper('apiExtension');

        $this->_updateButton('save', 'label', $this->helper->__('Save web hook'));
        $this->_updateButton('delete', 'label', $this->helper->__('Delete web hook'));

        $this->_addButton('saveandcontinue', array(
            'label' => $this->helper->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ), -100);


        $this->_formScripts[] = 'function saveAndContinueEdit(){ editForm.submit($(\'edit_form\').action+\'back/edit/\'); }';
    }

    /**
     * Gets grid header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('webhooks_data') && Mage::registry('webhooks_data')->getId()) {
            return $this->helper->__("Edit Webhook '%s'", $this->htmlEscape(Mage::registry('webhooks_data')->getId()));
        }

        return $this->helper->__('Add Item');
    }
}
