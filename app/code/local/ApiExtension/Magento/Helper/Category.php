<?php

class ApiExtension_Magento_Helper_Category extends Mage_Core_Model_Abstract
{
    /**
     * Categories text-path to ID hash.
     *
     * @var array
     */
    protected $_categories = array();

    /**
     * Categories text-path to ID hash with roots checking.
     *
     * @var array
     */
    protected $_categoriesWithRoots = array();

    /**
     * Store Id
     *
     * @var null
     */
    private $_storeId = null;

    /**
     * Root Category ID
     *
     * @var int
     */
    private $_rootCategoryId = null;

    /**
     * Category Default Values
     *
     * @var array
     */
    private $_categoryDefaults = array(
        'is_anchor' => 1,
        'include_in_menu' => 1,
        'is_active' => 1,
        'display_mode' => 'PRODUCTS',
    );

    /**
     * Get id of the store that we should Limit categories to
     *
     * @return $this
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * Set id of the store that we should Limit categories to
     *
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * Get Category Root Id that we should Limit categories to
     *
     * @return int
     */
    public function getRootCategoryId()
    {
        return $this->_rootCategoryId;
    }

    /**
     * Set Category Root Id that we should Limit categories to
     *
     * @param int $rootCategoryId
     *
     * @return $this
     */
    public function setRootCategoryId($rootCategoryId)
    {
        $this->_rootCategoryId = $rootCategoryId;

        return $this;
    }

    /**
     * Get Category Defaults settings
     * @return array
     */
    public function getCategoryDefaults()
    {
        return $this->_categoryDefaults;
    }

    /**
     * Set Category Defaults settings
     *
     * @param array $categoryDefaults
     */
    public function setCategoryDefaults($categoryDefaults)
    {
        $this->_categoryDefaults = $categoryDefaults;
    }

    /**
     * Finds a subcategory id from a path string
     *
     * @param $string
     *
     * @return bool
     */
    public function getIdFromPath($string)
    {
        if (!$this->_categories)
            $this->_initCategories();

        if (in_array($string, array_keys($this->_categories))) {
            return $this->_categories[$string];
        }
        return false;
    }

    /**
     * Get Root Category Id and option children categories
     *
     * @param null       $storeId
     * @param bool|false $child
     *
     * @return int
     */
    public function getRootCategory($storeId = null, $child = false)
    {
        if (!$storeId) {
            $this->_storeId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        }

        $this->_rootCategoryId = Mage::app()->getStore($this->getStoreId())->getRootCategoryId();

        //get children of root cat
        if ($child) {
            return Mage::getModel('catalog/category')->load($this->_rootCategoryId)->getChildrenCategories()->getData();
        }

        return $this->_rootCategoryId;
    }

    /**
     * Find Parent Categories with Current if given, else root category if returned as parent.
     *
     * @param        $string
     * @param string $separator
     *
     * @return array
     */
    public function findParentCategories($string, $separator = '/')
    {
        if (!$this->_categories)
            $this->_initCategories();

        $parentCategoryId = null;
        $categoriesWanted = explode($separator, $string);

        foreach ($categoriesWanted as $k => $level) {

            //shorten array by last
            array_pop($categoriesWanted);
            $str = rtrim(implode($separator, $categoriesWanted), $separator); // remove last seperator if present

            //find the parent path
            $parentCategoryId = $this->getIdFromPath($str);
            if ($parentCategoryId)
                break;
        }

        // if no parent category set root as parent category
        if (!$parentCategoryId)
            $parentCategoryId = $this->getRootCategoryId();

        $return = array(
            'parentCategoryId' => $parentCategoryId,
            'currentCategoryId' => $this->getIdFromPath($string),

            // create array of categories needed to create to reach that path
            'categoriesToCreate' => array_diff(explode($separator, $string), $categoriesWanted),
        );

        return $return;

    }

    /**
     * Check/Create Categories if needed, returns the CategoryId
     *
     * @param                $path
     * @param array | string $attr
     * @param int            $storeId
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function doCategory($path, $attr = null, $storeId = null)
    {

        if ($storeId) {
            $this->setStoreId($storeId);
        }

        // if category exists return id
        if ($id = $this->getIdFromPath(trim($path))) {
            return $id;
        }

        //set base data
        if (is_null($attr)) {
            $attr = $this->_categoryDefaults;
        }

        //allow to just pass category name to function
        elseif (is_string($attr)) {
            $attr = array_merge($this->_categoryDefaults, array('name' => $attr));
        }

        $parents = $this->findParentCategories($path);
        $curCategoryId = $parents['parentCategoryId'];

        //create categories and update parent category
        foreach ($parents['categoriesToCreate'] as $cat) {
            //set name
            $attr['name'] = $cat;
            $newCat = $this->createCategory($attr, $curCategoryId);

            if ($newCat->getId()) {
                $curCategoryId = $newCat->getId();
                continue;
            } else {
                Mage::throwException($newCat->getMessage());
            }
        }

        return $curCategoryId;
    }

    /**
     * Create Category, this is more an internal function better
     * use doCategory with has checks and create integrated
     *
     * @param      $attr
     * @param null $parentId
     *
     * @return Exception|mixed
     */
    public function createCategory($attr, $parentId = null)
    {

        //merge passed on data with defaults to avoid missing fields
        $attributes = array_merge($this->_categoryDefaults, $attr);

        $category = Mage::getModel('catalog/category');
        $category->setStoreId($this->getStoreId());

        //set data for category
        foreach ($attributes as $key => $value) {
            $category->setData($key, $value);
        }

        //skip if path is passed on
        if (!isset($attributes['path'])) {
            // always set parent Category Id
            if (!$parentId)
                $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;

            $parentCategory = Mage::getModel('catalog/category')->load($parentId);
            $category->setPath($parentCategory->getPath());
        }
        try {
            $newCat = $category->save();
        } catch (Exception $e) {
            return $e;
        }

        return $newCat;
    }

    /**
     * Initialize categories text-path to ID hash.
     *
     * @return Mage_ImportExport_Model_Import_Entity_Product
     */
    private function _initCategories()
    {
        $collection = Mage::getResourceModel('catalog/category_collection');

        //optional root filter
        if ($this->_storeId || $this->_rootCategoryId) {
            if (!$this->_rootCategoryId)
                $this->getRootCategory($this->_storeId);

            // filter by path of root category, 1 seems to be the default
            $collection->addPathsFilter('1/' . $this->_rootCategoryId);
        }

        $collection->addNameToResult();

        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */
        foreach ($collection as $category) {

            $structure = explode('/', $category->getPath());
            $pathSize = count($structure);

            if ($pathSize > 1) {
                $path = array();
                for ($i = 1; $i < $pathSize; $i++) {
                    $path[] = $collection->getItemById($structure[$i])->getName();
                }

                $rootCategoryName = array_shift($path);

                if (!isset($this->_categoriesWithRoots[$rootCategoryName])) {
                    $this->_categoriesWithRoots[$rootCategoryName] = array();
                }

                $index = implode('/', $path);
                $this->_categoriesWithRoots[$rootCategoryName][$index] = $category->getId();

                if ($pathSize > 2) {
                    $this->_categories[$index] = $category->getId();
                }
            }
        }

        return $this;
    }
}
