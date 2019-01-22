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
class ApiExtension_Magento_Model_Api2_ProductOption_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_ProductOption
{
    /**
     * @var ApiExtension_Magento_Helper_Data
     */
    private $helper;

    /**
     * @var string
     */
    private $cacheId = 'apiExtension-product-attributes';

    /**
     * @var array
     */
    private $excludeAttributes = array(
        'switch_category_attribute',
        'created_at',
        'updated_at',
    );

    /**
     * ApiExtension_Magento_Model_Api2_ProductOption_Rest_Admin_V1 constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('apiExtension');
    }

    /**
     * Gets attribute options for passed attribute code
     */
    public function _retrieveCollection()
    {
        $result = array();
        $attributeSetOptions = array();

        if (!$this->getRequest()->getParam('force') && ($data = Mage::app()->getCache()->load($this->cacheId)) !== false) {
            return unserialize($data);
        }

        //obtain all attribute sets
        $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection');
        $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();

        $defaultAttributeSetId = Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId();
        $attributeSets->setEntityTypeFilter($entityType);

        foreach ($attributeSets as $attributeSet) {
            $isRequired = false;
            $attributeSetOptions[$attributeSet->getId()] = $attributeSet->getAttributeSetName();

            //compare if the attribute belongs to a default attribute set
            if ($attributeSet->getId() == $defaultAttributeSetId) {
                $isRequired = true;
            }

            //obtain all attributes
            $attributes = Mage::getModel('catalog/product_attribute_api')->items($attributeSet->getId());

            /** @var Mage_Eav_Model_Entity_Attribute $attribute */
            foreach ($attributes as $attribute) {

                if (empty($attribute['type'])
                    || array_key_exists($attribute['attribute_id'], $result)
                    || in_array($attribute['code'], $this->excludeAttributes)
                ) {
                    continue;
                }

                // load full attribute object
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $_attribute */
                $_attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attribute['attribute_id']);

                if (!$this->filterAttributes($_attribute)) {
                    Mage::helper('apiExtension')->log('Skipped attribute with ID: ' . $attribute['attribute_id']);
                    continue;
                }

                $current = array(
                    'id' => $attribute['attribute_id'],
                    'attribute_code' => $attribute['code'],
                    'attribute_type' => $attribute['type'],
                    'required' => (int) ($_attribute->getIsRequired() == 1 && $isRequired)
                );

                // obtain all options
                if ($_attribute->usesSource()) {
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
        }

        $result['attribute_set_id'] = array(
            'attribute_code' => 'attribute_set_id',
            'attribute_type' => 'select',
            'options' => $attributeSetOptions,
            'required' => true
        );

        $result['store_id'] = array(
            'attribute_code' => 'store_id',
            'attribute_type' => 'select',
            'options' => $this->helper->getStoreOptions(),
            'required' => true
        );

        $result['website_id'] = array(
            'attribute_code' => 'website_id',
            'attribute_type' => 'select',
            'options' => $this->helper->getWebsitesOptions(true),
            'required' => true
        );

        $result['cust_group'] = array(
            'attribute_code' => 'cust_group',
            'attribute_type' => 'select',
            'options' => $this->helper->getCustomerGroups(),
            'required' => true
        );

        Mage::app()->getCache()->save(serialize($result), $this->cacheId, array('customer-product-attributes'), 1200);

        return $result;
    }

    /**
     * Filters attributes by criteria
     *
     * @param Mage_Eav_Model_Entity_Attribute $_attribute
     *
     * @return bool
     */
    private function filterAttributes($_attribute)
    {
        $applyTo = $_attribute->getApplyTo();

        if (!$_attribute->getIsVisible() ||
            (!empty($applyTo) &&
                !in_array(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, $applyTo) &&
                !in_array(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, $applyTo)
            )
        ) {
            return false;
        }

        return true;
    }
}
