<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_CategoriesTest extends ApiExtension_Magento_Base
{
    protected static $dataCategory = array(
        'entity_type_id' => '3',
        'attribute_set_id' => '3',
        'parent_id' => '63',
        'created_at' => '2013-01-14 11:12:53',
        'updated_at' => '2013-05-16 00:43:57',
        'available_sort_by' => null,
        'description' => null,
        'meta_keywords' => null,
        'meta_description' => null,
        'custom_layout_update' => null,
        'is_active' => '1',
        'include_in_menu' => '1',
        'landing_page' => '19',
        'is_anchor' => '0',
        'custom_apply_to_products' => '0',
        'name' => 'CategoryUnitTest',
        'meta_title' => null,
        'custom_design' => null,
        'page_layout' => null,
        'display_mode' => 'PRODUCTS_AND_PAGE',
        'custom_design_from' => null,
        'custom_design_to' => null,
        'filter_price_range' => null,
    );

    public function testGetCategories()
    {
        $categories = $this->get('categories');

        $this->assertTrue(is_array($categories));
        $this->assertFalse(empty($categories));
    }

    public function testGetCategoryById()
    {
        $categoryId = Mage::app()->getStore("default")->getRootCategoryId();
        $category = $this->get('categories/' . $categoryId);

        $this->assertTrue(is_array($category));
        $this->assertTrue(array_key_exists('entity_id', $category));
        $this->assertTrue(array_key_exists('name', $category));
    }

    public function testCreateCategory()
    {
        self::$dataCategory['parent_id'] = Mage::app()->getStore("default")->getRootCategoryId();
        $this->post('categories', self::$dataCategory);
    }

    public function testFilterAndDeleteCategory()
    {
        $category = $this->get('categories?filter[1][attribute]=name&filter[1][eq]=' . self::$dataCategory['name']);
        $category = reset($category);

        $result = $this->delete('categories/' . $category['entity_id']);

        $this->assertNull($result);
    }
}
