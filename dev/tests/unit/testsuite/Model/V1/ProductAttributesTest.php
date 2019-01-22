<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_ProductAttributesTest extends ApiExtension_Magento_Base
{
    public function testGetAttributesBySet()
    {
        $attributes = $this->get('/products/attributes/visibility/options');

        $this->assertEquals($attributes, array(
                array(
                    'label' => '-- Please Select --',
                    'value' => '',
                ),
                array(
                    'label' => 'Not Visible Individually',
                    'value' => 1,
                ),
                array(
                    'label' => 'Catalog',
                    'value' => 2,
                ),
                array(
                    'label' => 'Search',
                    'value' => 3,
                ),
                array(
                    'label' => 'Catalog, Search',
                    'value' => 4,
                ),
            )
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testGetAttributesBySetNotFound()
    {
        $this->get('/products/attributes/visibility3/options');
    }
}
