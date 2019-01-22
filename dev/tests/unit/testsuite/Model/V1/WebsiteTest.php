<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_WebsiteTest extends ApiExtension_Magento_Base
{
    public function testWebsites()
    {
        $attributes = $this->get('/stores/websites');

        $this->assertTrue(is_array($attributes));
    }
}
