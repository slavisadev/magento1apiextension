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
class ApiExtension_Magento_Model_Api2_Categories_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_Categories
{
    /**
     * Get category by id
     */
    public function _retrieve()
    {
        return $this->getCategory()->getData();
    }

    /**
     * Creates category
     *
     * @param array $data
     *
     * @return string
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _create(array $data)
    {
        /* @var $category Mage_Catalog_Model_Category */
        $category = Mage::getModel('catalog/category');
        $category->setStoreId((int)$this->getRequest()->getParam('store', 1));

        try {
            $this->_prepareUpdatedCategory($category, $data);
            $category->save();
        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Mage_Api2_Exception $e) {
            throw $e;
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }

        return $this->_getLocation($category);
    }

    /**
     * Updated category by id and store id
     *
     * @param array $data
     *
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _update(array $data)
    {
        try {
            $category = $this->getCategory();
            $storeId = $this->getRequest()->getParam('store');

            if (!is_null($storeId)) {
                $category->setStoreId($storeId);
            }

            $this->_prepareUpdatedCategory($category, $data);
            $category->save();
        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Mage_Api2_Exception $e) {
            throw $e;
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
    }

    /**
     * Deletes category by id
     *
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _delete()
    {
        try {
            $category = $this->getCategory();
            $category->delete();
        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
    }

    /**
     * Retrieves categories
     *
     * @return array
     */
    public function _retrieveCollection()
    {
        $data = $this->_getCategories()->load()->toArray();

        return isset($data['items']) ? $data['items'] : $data;
    }

    /**
     * Prepares category for update
     *
     * @param Mage_Catalog_Model_Category $category
     * @param array $data
     *
     * @return $this
     */
    protected function _prepareUpdatedCategory(Mage_Catalog_Model_Category $category, array $data)
    {
        $category->addData($data);

        // auto correct path if new parent is submitted
        if (isset($data['parent_id'])) {
            /* @var $parent Mage_Catalog_Model_Category */
            $parent = Mage::getModel('catalog/category')->load($data['parent_id']);
            $category->setPath($parent->getPath());
        }

        $this->_validateCategory($category);
    }

    /**
     * Validates category
     *
     * @param Mage_Catalog_Model_Category $category
     *
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    protected function _validateCategory(Mage_Catalog_Model_Category $category)
    {
        $errors = $category->validate();

        if (@$errors['available_sort_by'] === true) {
            unset($errors['available_sort_by']);
        }

        if (@$errors['default_sort_by'] === true) {
            unset($errors['default_sort_by']);
        }

        // parent_id should be required but is not EAV
        if (!$category->hasParentId()) {
            $errors['parent_id'] = true;
        }

        if ($errors) {
            foreach ($errors as $attribute => $error) {

                if ($error === true) {
                    $error = $attribute . ' is required';
                }

                $this->_error($error, Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
            }

            $this->_critical(self::RESOURCE_DATA_PRE_VALIDATION_ERROR);
        }
    }

    /**
     * Gets filtered categories
     *
     * @return Mage_Catalog_Model_Resource_Category_Collection
     */
    protected function _getCategories()
    {
        /* @var $categories Mage_Catalog_Model_Resource_Category_Collection */
        $categories = Mage::getResourceModel('catalog/category_collection');
        $categories->setStoreId($this->getRequest()->getParam('store'));

        if (($parentId = $this->getRequest()->getParam('parent'))) {
            $categories->addAttributeToFilter('parent_id', $parentId);
        }

        $categories->addAttributeToSelect('*');

        // global root must always be hidden
        $categories->addFieldToFilter('path', array('neq' => '1'));
        $this->_applyCollectionModifiers($categories);

        return $categories;
    }

    protected function _getResourceAttributes()
    {
        return $this->getEavAttributes(true, true);
    }

    /**
     * Gets category by id and store
     *
     * @return Mage_Catalog_Model_Category
     */
    private function getCategory()
    {
        /* @var $category Mage_Catalog_Model_Category */
        $category = Mage::getModel('catalog/category');
        $categoryId = $this->getRequest()->getParam('id');
        $category->setStoreId($this->getRequest()->getParam('store'));
        $category->load($categoryId);

        if (!$category->getId()) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return $category;
    }
}
