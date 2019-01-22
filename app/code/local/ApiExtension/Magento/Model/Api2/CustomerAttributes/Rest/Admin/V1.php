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
class ApiExtension_Magento_Model_Api2_CustomerAttributes_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_CustomerAttributes
{
    /**
     * @var string
     */
    private $cacheId = 'apiExtension-customer-attributes';

    /**
     * @var ApiExtension_Magento_Helper_Data
     */
    private $helper;

    /**
     * @var array
     */
    private $excludeAttributes = array(
        'rp_token_created_at',
        'rp_token',
        'reward_update_notification',
        'reward_warning_notification',
        'password_hash',
        'confirmation',
        'is_default_billing',
        'is_default_shipping',
        'created_at',
        'updated_at'
    );

    /**
     * ApiExtension_Magento_Model_Api2_CustomerAttributes_Rest_Admin_V1 constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('apiExtension');
    }

    /**
     * Gets customer attributes
     */
    public function _retrieveCollection()
    {
        $result = array();

        if (!$this->getRequest()->getParam('force') && ($data = Mage::app()->getCache()->load($this->cacheId)) !== false) {
            return unserialize($data);
        }

        //obtain all attributes
        $attributes = Mage::getModel('customer/customer')->getAttributes();
        foreach ($attributes as $attribute) {

            if (!$attribute->getId() || in_array($attribute->getAttributeCode(), $this->excludeAttributes) || array_key_exists($attribute->getId(), $result)) {
                continue;
            }

            // load full attribute object
            $_attribute = Mage::getModel('eav/config')->getAttribute('customer', $attribute->getAttributeCode());

            $current = array(
                'id' => $attribute->getId(),
                'attribute_code' => $attribute->getAttributeCode(),
                'attribute_type' => $attribute->getFrontendInput(),
                'required' => $attribute->getIsRequired()
            );

            // obtain all options
            if ($attribute->getFrontendInput() === 'select' && $_attribute && $_attribute->getSource()) {

                $options = array();
                foreach ($_attribute->getSource()->getAllOptions(true, true) as $instance) {
                    if ($instance['label'] && $instance['value'] && !is_array($instance['value'])) {
                        $options[$instance['value']] = $instance['label'];
                    }
                }

                $current['options'] = $options;
            }

            $result[$current['attribute_code']] = $current;
        }

        $result['store_id'] = array(
            'attribute_code' => 'store_id',
            'attribute_type' => 'select',
            'options' => $this->helper->getStoreOptions(),
            'required' => true
        );

        $result['website_id'] = array(
            'attribute_code' => 'website_id',
            'attribute_type' => 'select',
            'options' => $this->helper->getWebsitesOptions(),
            'required' => true
        );

        $result['country_id'] = array(
            'attribute_code' => 'country_id',
            'attribute_type' => 'select',
            'options' => $this->helper->getCountriesOptions(),
            'required' => true
        );

        $result['region'] = array(
            'attribute_code' => 'region',
            'attribute_type' => 'select',
            'options' => $this->helper->getRegionOptions(),
            'required' => true
        );

        Mage::app()->getCache()->save(serialize($result), $this->cacheId, array('customer-custom-attributes'), 1200);

        return $result;
    }
}
