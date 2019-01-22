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
class ApiExtension_Magento_Model_Api2_ConfigurableProductLinks_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_ConfigurableProductLinks
{
    const HELPER_NAME = 'magento';
    const NOT_FOUND_CONFIGURABLE = 'Configurable product not found.';
    const NOT_FOUND_SIMPLE = 'Simple product not found.';
    const NOT_CONFIGURABLE_PRODUCT = 'Product is not configurable.';
    const SIMPLE_PRODUCT_ALREADY_EXISTS = 'Simple products already exists for this configurable product.';
    const ASSIGNED_ATTRIBUTE_IS_NOT_APPLICABLE = 'Assigned attribute is not applicable for this simple products.';
    const ATTRIBUTE_CODE_NOT_FOUND = 'Attribute code not found';
    const ATTRIBUTES_NOT_FOUND = 'Attributes not found';
    const ATTRIBUTES_INVALID_FORMAT = 'Attributes must be array';
    const ATTRIBUTE_SETS_INCOMPATIBLE = 'Attribute sets must be a match';

    /**
     * Deletes product child
     *
     * @return string
     * @throws Exception
     */
    public function _delete()
    {
        $childrenId = $this->getRequest()->getParam('childSku');
        $productId = $this->getRequest()->getParam('id'); // id of configurable product

        $this->manageConfigurableProductOptions($productId, $childrenId, array(), true);
    }

    /**
     * Adds variations to configurable product
     *
     * @param array $data
     * @return bool
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _create(array $data)
    {
        $productId = $this->getRequest()->getParam('id'); // id of configurable product
        $attributesCodes = isset($data['option']['attribute_id']) ? $data['option']['attribute_id'] : null;
        $childProduct = $data['option']['product_id']; // product that needs to be connected to configurable product

        if (is_null($attributesCodes)) {
            $this->_critical(self::ATTRIBUTES_NOT_FOUND);
        }

        if (!is_array($attributesCodes)) {
            $this->_critical(self::ATTRIBUTES_INVALID_FORMAT);
        }

        $this->manageConfigurableProductOptions($productId, $childProduct, $attributesCodes);

        return $this->_getLocation($productId);
    }

    /**
     * Gets configurable product
     *
     * @param $productId
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    private function getConfigurableProduct($productId)
    {
        /** @var Mage_Catalog_Model_Product $configProduct */
        $configProduct = Mage::helper('catalog/product')->getProduct($productId, $this->_getStore()->getId());

        // Validate if configurable product exists by id
        if (!$configProduct->getId()) {
            $this->_critical(self::NOT_FOUND_CONFIGURABLE);
        }

        // Check if product type is configurable
        if (!$configProduct->isConfigurable()) {
            $this->_critical(self::NOT_CONFIGURABLE_PRODUCT);
        }

        return $configProduct;
    }

    /**
     * Gets simple product
     *
     * @param $productId
     * @return Mage_Core_Model_Abstract
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    private function getSimpleProduct($productId)
    {
        $simpleProduct = Mage::helper('catalog/product')->getProduct($productId, $this->_getStore()->getId());

        // Validate if simple product exists by id
        if (!$simpleProduct->getId()) {
            $this->_critical(self::NOT_FOUND_SIMPLE);
        }

        return $simpleProduct;
    }

    /**
     * Gets attributes as key-value pair for passed attribute codes
     *
     * @param $attributesCodes
     * @return array
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    private function getAttributesByCodes($attributesCodes)
    {
        $result = array();

        foreach ($attributesCodes as $attributeCode) {
            $attributeId = Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $attributeCode);

            if (!$attributeId) {
                $this->_critical(self::ATTRIBUTE_CODE_NOT_FOUND);
            }

            $result[$attributeId] = $attributeCode;
        }

        return $result;
    }

    /**
     * Gets configurable attributes for configurable product
     *
     * @param $configProduct
     * @return array
     */
    private function getConfigurableAttributes($configProduct)
    {
        $result = array();

        foreach ($configProduct->getTypeInstance()->getConfigurableAttributes($configProduct) as $attribute) {
            $result[$attribute->getProductAttribute()->getId()] = $attribute->getProductAttribute()->getAttributeCode();
        }

        return $result;
    }

    /**
     * Creates, updates and deletes configurable product options
     *
     * @param $attributesCodes
     * @param $productId
     * @param $childProduct
     * @param $delete
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    private function manageConfigurableProductOptions($productId, $childProduct, $attributesCodes, $delete = false)
    {
        /** @var Mage_Catalog_Model_Product $configProduct */
        $configProduct = $this->getConfigurableProduct($productId);

        /** @var Mage_Catalog_Model_Product $simpleProduct */
        $simpleProduct = $this->getSimpleProduct($childProduct);

        if (!$this->matchingAttributeSets($configProduct, $simpleProduct)) {
            $this->_critical(self::ATTRIBUTE_SETS_INCOMPATIBLE);
        }

        /**
         * Get old associated simple products for configurable product and merge them with newly assigned skus? SKUs?
         */
        $usedIds = array_shift(Mage::getModel('catalog/product_type_configurable')->getChildrenIds($configProduct->getId()));
        $assignedIds = array_unique(array_merge(array($childProduct), $usedIds));

        if (empty($assignedIds)) {
            $this->_critical(self::SIMPLE_PRODUCT_ALREADY_EXISTS);
        }

        $attributes = $this->getAttributesByCodes($attributesCodes);
        $configurableAttributes = $this->getConfigurableAttributes($configProduct);

        if (empty($attributes)) {
            $attributes = $configurableAttributes;
        }

        if (empty($configurableAttributes)) {
            $configProduct->getTypeInstance()->setUsedProductAttributeIds(array_keys($attributes));
        }

        $usedIds[] = $simpleProduct->getId();

        $configurableProductsData = array();
        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();

        foreach (array_unique($usedIds) as $childId) {
            /** @var Mage_Catalog_Model_Product $simpleProduct */
            $childProduct = Mage::getModel('catalog/product')->load($childId);

            // skip product that needs to be deleted
            if ($delete && $childId === $simpleProduct->getId()) {
                continue;
            }

            foreach ($attributes as $attributeId => $attributeCode) {
                $simpleProductsData = array(
                    'label' => $childProduct->getAttributeText($attributeCode),
                    'attribute_id' => $attributeId,
                    'value_index' => (int)$childProduct->getData($attributeCode),
                    'is_percent' => 0,
                    'pricing_value' => $simpleProduct->getPrice(),
                );

                $configurableProductsData[$childId] = $simpleProductsData;
                $configurableAttributesData[0]['values'][] = $simpleProductsData;

            }
        }

        $configProduct->setConfigurableProductsData($configurableProductsData);
        $configProduct->setConfigurableAttributesData($configurableAttributesData);
        $configProduct->setCanSaveConfigurableAttributes(true);
        try {
            $configProduct->save();
        } catch (Exception $exception) {
            Mage::log($exception, null, 'system.log', true);
        }
    }

    /**
     * A method for attribute sets comparison
     * @param $configProduct
     * @param $simpleProduct
     *
     * @return bool
     */
    public function matchingAttributeSets($configProduct, $simpleProduct)
    {
        $attrSet1 = $configProduct->getAttributeSetId();
        $attrSet2 = $simpleProduct->getAttributeSetId();
        return $attrSet1 == $attrSet2;
    }
}
