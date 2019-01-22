<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_ProductAttributeOptionTest extends ApiExtension_Magento_Base
{
    public function testGetAttributesBySet()
    {
        $actual = count($this->get('/products/attribute-sets/11/attributes'));
        $expected = count(Mage::getModel('catalog/product_attribute_api')->items(11));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Exception
     */
    public function testGetAttributesBySetNotFound()
    {
        $this->get('/products/attribute-sets/1444/attributes');
    }
}
