<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_VersionTest extends ApiExtension_Magento_Base
{
    public function testConnection()
    {
        $connected = $this->get('version');

        $this->assertTrue(is_array($connected));
    }

    public function testExtensionVersion()
    {
        $moduleName = ApiExtension_Magento_Helper_Data::MODULE_NAME;
        $extensionVersion = Mage::getConfig()->getNode()->modules->$moduleName->version;

        $data = $this->get('version');

        $this->assertEquals($data['module_version'], $extensionVersion);
        $this->assertEquals($data['magento_version'], Mage::getVersion());
    }
}
