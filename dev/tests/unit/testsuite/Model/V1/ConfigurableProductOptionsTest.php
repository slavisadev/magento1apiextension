<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_ConfigurableProductOptionsTest extends ApiExtension_Magento_Base
{
    protected static $sourceProductData = array(
        'sku' => 'Config_product',
        'attribute_set_id' => 11,
        'name' => 'Configurable Product',
        'visibility' => 4,
        'description' => 'Some description....',
        'short_description' => 'Some description....',
        'tax_class_id' => '6',
        'type_id' => 'configurable',
        'price' => 99.95,
        'status' => 1,
    );

    public function testDeleteConfigurableProduct()
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);

        $this->delete('/products/' . $product->getId());
    }

    public function testCreateConfigurableProduct()
    {
        $attributes = $this->post('/products', self::$sourceProductData);

        $this->assertNull($attributes);
    }

    public function testLinkProducts()
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);

        $this->post('/configurable-products/' . $product->getId() . '/child/', array(
            'option' => array(
                'attribute_id' => array('color', 'material'),
                'product_id' => '541'
            )
        ));

        $configProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);
        $usedIds = array_shift(Mage::getModel('catalog/product_type_configurable')->getChildrenIds($configProduct->getId()));

        $this->assertTrue(in_array('541', $usedIds));
    }

    /**
     * @depends testLinkProducts
     */
    public function testAddVariation() {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);

        $this->post('/configurable-products/' . $product->getId() . '/child/', array(
            'option' => array(
                'attribute_id' => array('color', 'material'),
                'product_id' => '377'
            )
        ));

        $this->post('/configurable-products/' . $product->getId() . '/child/', array(
            'option' => array(
                'attribute_id' => array('color', 'material'),
                'product_id' => '374'
            )
        ));

        $configProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);
        $usedIds = array_shift(Mage::getModel('catalog/product_type_configurable')->getChildrenIds($configProduct->getId()));

        $this->assertTrue(in_array('541', $usedIds));
        $this->assertTrue(in_array('377', $usedIds));
        $this->assertTrue(in_array('374', $usedIds));
    }

    /**
     * @expectedException Exception
     * @depends testLinkProducts
     */
    public function testWrongAttributeCode() {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);

        $this->post('/configurable-products/' . $product->getId() . '/child/', array(
            'option' => array(
                'attribute_id' => array('color', 'material', 'some-attribute'),
                'product_id' => '374'
            )
        ));

        $configProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);
        $usedIds = array_shift(Mage::getModel('catalog/product_type_configurable')->getChildrenIds($configProduct->getId()));

        $this->assertTrue(in_array('541', $usedIds));
        $this->assertTrue(in_array('377', $usedIds));
        $this->assertTrue(in_array('374', $usedIds));
    }

    /**
     * @depends testWrongAttributeCode
     */
    public function testDeleteVariation() {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);

        $this->delete('/configurable-products/' . $product->getId() . '/children/374');

        $configProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);
        $usedIds = array_shift(Mage::getModel('catalog/product_type_configurable')->getChildrenIds($configProduct->getId()));

        $this->assertTrue(in_array('541', $usedIds));
        $this->assertTrue(in_array('377', $usedIds));
        $this->assertFalse(in_array('374', $usedIds));
    }

    /**
     * @depends testDeleteVariation
     */
    public function testGetConfigurableProductData() {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', self::$sourceProductData['sku']);

        $data = $this->get('/products/' . $product->getId());

        $this->assertTrue(array_key_exists('configurable_product_links', $data));
        $this->assertTrue(array_key_exists('configurable_product_options', $data));
    }

}
