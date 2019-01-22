<?php

class ApiExtension_Magento_Model_Catalog_Api2_Product_Rest_Admin_V1 extends Mage_Catalog_Model_Api2_Product_Rest_Admin_V1
{
    private $groupPriceAttribute = null;

    /**
     * Retrieve product data
     *
     * @return array
     */
    public function _retrieve()
    {
        $data = parent::_retrieve();

        if ($data['type_id'] === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            /** @var Mage_Catalog_Model_Product $configProduct */
            $configProduct = Mage::getModel('catalog/product')->load($data['entity_id']);

            $data['configurable_product_options'] = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
            $data['configurable_product_links'] = $this->getChildrenIds($configProduct);
        }

        return $data;
    }

    /**
     * Retrieve list of products
     *
     * @return array
     */
    public function _retrieveCollection()
    {
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $store = $this->_getStore();

        $collection->setStoreId($store->getId());
        $collection->addAttributeToSelect(array_keys(
            $this->getAvailableAttributes($this->getUserType(), Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ)
        ));

        $collection->joinField(
            'qty',
            'cataloginventory/stock_item',
            'qty',
            'product_id = entity_id',
            null,
            'left'
        )->joinTable('cataloginventory/stock_item', 'product_id=entity_id', array(
            'backorders' => 'backorders',
            'is_in_stock' => 'is_in_stock',
            'min_sale_qty' => 'min_sale_qty',
            'max_sale_qty' => 'max_sale_qty',
            'low_stock_date' => 'low_stock_date',
            'manage_stock' => 'manage_stock',
            'stock_status_changed_auto' => 'stock_status_changed_auto',
            'enable_qty_increments' => 'enable_qty_increments',
            'qty_increments' => 'qty_increments',
        ))->addAttributeToSelect('*');

        $this->_applyCategoryFilter($collection);
        $this->_applyCollectionModifiers($collection);

        $products = $collection->load();

        foreach ($products as $id => &$product) {

            $product['stock_data'] = array(
                'qty' => $product->getQty(),
                'backorders' => $product->getBackorders(),
                'min_sale_qty' => $product->getMinSaleQty(),
                'max_sale_qty' => $product->getMaxSaleQty(),
                'low_stock_date' => $product->getLowStockDate(),
                'manage_stock' => $product->getData('manage_stock'),
                'stock_status_changed_auto' => $product->getStockStatusChangedAuto(),
                'enable_qty_increments' => $product->getEnableQtyIncrements(),
                'qty_increments' => $product->getQtyIncrements(),
                'is_in_stock' => $product->getIsInStock(),
            );

            $productCategories = $this->getCategories($product);
            if ($productCategories) {
                $product['categories'] = $productCategories;
            }

            $product['tier_price'] = $product->getFormatedTierPrice();
            $product['group_price'] = $this->getGroupPrice($product);

            unset($product['qty'], $product['backorders'], $product['stock_item']);

            if ($product['type_id'] !== Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $product['has_parent'] = $this->hasParent($id);
                continue;
            }

            /** @var Mage_Catalog_Model_Product $configProduct */
            $configProduct = Mage::getModel('catalog/product')->load($id);

            $product['configurable_product_options'] = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
            $product['configurable_product_links'] = $this->getChildrenIds($configProduct);

        }

        return $products->toArray();
    }

    /**
     * @param $product
     * @return array | boolean
     */
    public function getCategories($product)
    {
        $cats = $product->getCategoryIds();

        if (count($cats) < 1)
            return false;

        $categories = array();

        foreach ($cats as $category_id) {
            $_cat = Mage::getModel('catalog/category')->load($category_id);
            $categories[$_cat->getId()] = $this->slugifyPath($_cat->getPath());
        }

        return $categories;
    }

    /**
     * @return ApiExtension_Magento_Helper_Category
     */
    public function getHelper()
    {
        return Mage::helper('apiExtension/category');
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function slugifyPath($path)
    {
        $pathIds = explode('/', $path);
        $textPath = '';
        foreach ($pathIds as $pathId) {
            $_cat = Mage::getModel('catalog/category')->load($pathId);
            $textPath .= $_cat->getName() . '/';
        }

        return substr($textPath, 0, -1);
    }

    /**
     * Create product
     *
     * @param array $data
     *
     * @return string
     */
    public function _create(array $data)
    {
        if ($this->checkProductExists($data)) {
            return $this->_update($data);
        }

        $type = $data['type_id'];

        if ($type !== Mage_Catalog_Model_Product_Type::TYPE_SIMPLE && $type !== Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $this->_critical("Creation of products with type '$type' is not implemented", Mage_Api2_Model_Server::HTTP_METHOD_NOT_ALLOWED);
        }

        if ($type == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            unset($data['weight']);
        }

        /* @var $validator Mage_Catalog_Model_Api2_Product_Validator_Product */
        $validator = Mage::getModel('catalog/api2_product_validator_product', array(
            'operation' => self::OPERATION_CREATE
        ));

        if (isset($data['stock_data']['is_qty_decimal'])) {
            $data['stock_data']['is_qty_decimal'] = intval($data['stock_data']['is_qty_decimal']);
        }
        if (isset($data['stock_data']['enable_qty_increments'])) {
            $data['stock_data']['enable_qty_increments'] = intval($data['stock_data']['enable_qty_increments']);
        }
        if (isset($data['stock_data']['is_in_stock'])) {
            $data['stock_data']['is_in_stock'] = intval($data['stock_data']['is_in_stock']);
        }

        if (!$validator->isValidData($data)) {
            foreach ($validator->getErrors() as $error) {
                $this->_error($error, Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
            }

            $this->_critical(self::RESOURCE_DATA_PRE_VALIDATION_ERROR);
        }

        $storeId = isset($data['store_id']) ? $data['store_id'] : Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')
            ->setStoreId($storeId)
            ->setAttributeSetId($data['attribute_set_id'])
            ->setTypeId($type)
            ->setSku($data['sku']);

        foreach ($product->getMediaAttributes() as $mediaAttribute) {
            $product->setData($mediaAttribute->getAttributeCode(), 'no_selection');
        }

        $this->_prepareDataForSave($product, $data);

        try {
            $product->validate();

            $extractedCategories = $this->attachCategories($data['categories']);

            $product->setCategoryIds($extractedCategories);

            $product->save();
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_UNKNOWN_ERROR);
        }

        return $this->_getLocation($product);
    }

    /**
     * @param $categories
     *
     * @return array
     */
    public function attachCategories($categories)
    {
        $paths = $categories['paths'];
        $skip = (bool)$categories['skip'];

        $extractedCategories = array();

        foreach ($paths as $path) {
            $extractedCategories[] = array_pop($this->getCategoryIdsByPath($path, $skip));
        }

        return $extractedCategories;
    }

    /**
     * @param $path
     *
     * @return array
     */
    public function getCategoryIdsByPath($path, $skip)
    {
        $names = explode('/', $path);

        $categoryIds = array();

        $previous = null;
        foreach ($names as $name) {
            $id = $this->getCategoryIdByName($name);

            if (!is_null($id)) {
                $categoryIds[] = $this->getCategoryIdByName($name);
            } else {
                if ($skip) {
                    continue;
                }
                $parentId = $this->getParentId($previous);
                $categoryIds[] = $this->createNewCategory($parentId, $name);
            }
            $previous = $name;
        }

        return $categoryIds;
    }

    /**
     * @param $parentId
     * @param $name
     *
     * @return int
     */
    public function createNewCategory($parentId, $name)
    {
        return $this->createCategory($this->getHelper()->getCategoryDefaults(), $parentId, $name)->getId();
    }

    /**
     * @param $previous
     * @return mixed
     */
    public function getParentId($previous)
    {
        return $this->getCategoryIdByName($previous);
    }

    /**
     * @param $attr
     * @param $parentId
     * @param $name
     *
     * @return $id
     */
    public function createCategory($attr, $parentId, $name)
    {
        $attr['name'] = $name;
        return $this->getHelper()->createCategory($attr, $parentId);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getCategoryIdByName($name)
    {
        $category = Mage::getResourceModel('catalog/category_collection')
            ->addFieldToFilter('name', $name)
            ->getFirstItem();

        return $category->getId();
    }

    /**
     * @return string
     */
    public function getServerType()
    {
        if (preg_match('/nginx/', $_SERVER['SERVER_SOFTWARE'])) {
            $type = 'nginx';
        } else if (preg_match('/Apache/', $_SERVER['SERVER_SOFTWARE'])) {
            $type = 'apache';
        } else {
            $type = 'apache';
        }
        return $type;
    }

    /**
     * Update product by its ID
     *
     * @param array $data
     *
     * @return string
     */
    public function _update(array $data)
    {
        Mage::helper('apiExtension')->log($data);

        /** acquire the product that should be edited */
        $product = $this->productToUpdate($data, $this->getRequest()->getParam('id'));


        /* @var $validator Mage_Catalog_Model_Api2_Product_Validator_Product */
//        $validator = Mage::getModel('catalog/api2_product_validator_product', array(
//            'operation' => self::OPERATION_UPDATE,
//            'product' => $product
//        ));

//        if (!$validator->isValidData($data)) {
//            foreach ($validator->getErrors() as $error) {
//                $this->_error($error, Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
//            }
//
//            $this->_critical(self::RESOURCE_DATA_PRE_VALIDATION_ERROR);
//        }

        if (isset($data['sku'])) {
            $product->setSku($data['sku']);
        }

        // attribute set and product type cannot be updated
        unset($data['attribute_set_id']);
        unset($data['type_id']);

        $this->_prepareDataForSave($product, $data);

        try {
            $product->validate();
            $product->save();

            return $this->_getLocation($product);

        } catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
            $this->_critical(sprintf('Invalid attribute "%s": %s', $e->getAttributeCode(), $e->getMessage()),
                Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_UNKNOWN_ERROR);
        }
    }

    /**
     * Get product group price
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return float
     */
    private function getGroupPrice($product)
    {
        $attribute = $product->getResource()->getAttribute('group_price');
        if ($attribute) {
            $attribute->getBackend()->afterLoad($product);
            return $product->getData('group_price');
        }
    }

    /**
     * check if product has parent
     *
     * @param $productId
     *
     * @return bool
     */
    private function hasParent($productId)
    {
        return count(Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId)) >= 1;
    }

    /**
     * @param $data
     * @param $idFromRequest
     *
     * @return bool|Mage_Catalog_Model_Product
     */
    private function productToUpdate($data, $idFromRequest)
    {
        /** @var $productHelper Mage_Catalog_Helper_Product */
        $productHelper = Mage::helper('catalog/product');

        if (isset($idFromRequest)) {
            return $productHelper->getProduct($idFromRequest, $this->_getStore()->getId());
        }

        $_catalog = Mage::getModel('catalog/product');
        $_productId = $_catalog->getIdBySku($data['sku']);
        $_product = Mage::getModel('catalog/product')->load($_productId);

        if (!$_product->getId()) {
            return false;
        }

        return $productHelper->getProduct($_product->getId(), $this->_getStore()->getId());
    }

    /**
     * Checks if product exists
     *
     * @param $data
     *
     * @return bool
     */
    private function checkProductExists($data)
    {
        $_catalog = Mage::getModel('catalog/product');

        $_productId = $_catalog->getIdBySku($data['sku']);
        $_product = $_catalog->load($_productId);

        return $_product->getId() ? true : false;
    }

    /**
     * Gets configurable product child ids
     *
     * @param $configProduct
     *
     * @return array
     */
    private function getChildrenIds($configProduct)
    {
        $childrenIds = array_shift(Mage::getModel('catalog/product_type_configurable')->getChildrenIds($configProduct->getId()));

        return array_keys($childrenIds);
    }
}
