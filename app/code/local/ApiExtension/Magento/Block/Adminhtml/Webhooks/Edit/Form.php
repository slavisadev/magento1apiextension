<?php

class ApiExtension_Magento_Block_Adminhtml_Webhooks_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * @var Mage_Core_Helper_Abstract|ApiExtension_Magento_Helper_Data
     */
    private $helper;

    /**
     * @var Mage_Adminhtml_Model_Session|Mage_Core_Model_Abstract
     */
    private $session;

    /**
     * ApiExtension_Magento_Block_Adminhtml_Webhooks_Edit_Form constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->helper = Mage::helper('apiExtension');
        $this->session = Mage::getSingleton('adminhtml/session');
    }

    /**
     * Prepares form for web hook edit
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data',
            )
        );

        $fieldSet = $form->addFieldset('magento_form', array('legend' => $this->helper->__('Webhook information')));

        $fieldSet->addField('code', 'select', array(
            'label' => $this->helper->__('Code'),
            'class' => 'required-entry',
            'name' => 'code',
            'values' => ApiExtension_Magento_Block_Adminhtml_Webhooks_Grid::getAvailableHooks()
        ));

        $fieldSet->addField('url', 'text', array(
            'label' => $this->helper->__('Callback URL'),
            'class' => 'required-entry',
            'name' => 'url',
        ));

        $fieldSet->addField('description', 'textarea', array(
            'label' => $this->helper->__('Description'),
            'name' => 'description',
        ));

        $fieldSet->addField('data', 'textarea', array(
            'label' => $this->helper->__('Data'),
            'name' => 'data',
        ));

        $fieldSet->addField('token', 'text', array(
            'label' => $this->helper->__('Token'),
            'name' => 'token',
        ));

        $fieldSet->addField('active', 'select', array(
            'label' => $this->helper->__('Active'),
            'values' => ApiExtension_Magento_Block_Adminhtml_Webhooks_Grid::getOptionsForActive(),
            'name' => 'active',
        ));

        if ($this->session->getWebhooksData()) {
            $form->setValues($this->session->getWebhooksData());
            $this->session->setWebhooksData(null);
        } elseif (Mage::registry('webhooks_data')) {
            $form->setValues(Mage::registry('webhooks_data')->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
