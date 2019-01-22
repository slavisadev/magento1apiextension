<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_StoreviewTest extends ApiExtension_Magento_Base
{
    public function testStoreviews()
    {
        $attributes = $this->get('/store/storeViews');

        $this->assertTrue(is_array($attributes));
    }
}
