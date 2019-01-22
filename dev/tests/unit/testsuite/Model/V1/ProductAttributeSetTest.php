<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_ProductAttributeSetTest extends ApiExtension_Magento_Base
{
    public function testGetAttributesSetList()
    {
        $attributes = $this->get('/eav/attribute-sets/list');

        $this->assertTrue(is_array($attributes));
    }

    public function testGetAttributeSetById()
    {
        $attributes = $this->get('/eav/attribute-sets/4');

        $this->assertArrayHasKey('attribute_set_id', $attributes);
        $this->assertArrayHasKey('entity_type_id', $attributes);
        $this->assertArrayHasKey('attribute_set_name', $attributes);
        $this->assertArrayHasKey('sort_order', $attributes);
    }

    /**
     * @expectedException Exception
     */
    public function testGetAttributeSetByIdNotFound()
    {
        $this->get('/eav/attribute-sets/asda');
    }
}
