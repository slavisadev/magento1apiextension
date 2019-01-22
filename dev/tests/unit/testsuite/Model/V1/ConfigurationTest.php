<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_ConfigurationTest extends ApiExtension_Magento_Base
{
    public function testGetBaseUrlConfiguration()
    {
        $baseUrlConfig = $this->get('configuration', array('path' => 'web/unsecure/base_url'));

        $this->assertStringStartsWith('http', $baseUrlConfig);
    }

    /**
     * @expectedException Exception
     */
    public function testNotFoundConfiguration()
    {
        $this->get('configuration', array('path' => 'some/path/path'));
    }

}
