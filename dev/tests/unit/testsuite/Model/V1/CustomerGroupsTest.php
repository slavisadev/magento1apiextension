<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_CustomerGroupsTest extends ApiExtension_Magento_Base
{
    protected static $dataCustomerGroup = array(
        'customer_group_code' => 'UnitTest',
        'tax_class_id' => 3,
    );

    public function testGetCustomerGroups()
    {
        $customerGroups = $this->get('customerGroups');

        $this->assertTrue(is_array($customerGroups));
        $this->assertFalse(empty($customerGroups));
    }

    public function testGetCustomerGroupById()
    {
        $customerGroup = $this->get('customerGroups/0');

        $this->assertTrue(is_array($customerGroup));
        $this->assertTrue(array_key_exists('customer_group_code', $customerGroup));
        $this->assertTrue(array_key_exists('customer_group_id', $customerGroup));
        $this->assertTrue(array_key_exists('tax_class_id', $customerGroup));
    }

    public function testCreateCustomerGroup()
    {
        $result = $this->post('customerGroups', self::$dataCustomerGroup);

        $this->assertStringEndsWith('api/rest/customerGroups', $result['url']);
        $this->assertEquals(200, $result['http_code']);
    }

    public function testGetCustomerGroupsFilter()
    {
        $customerGroups = $this->get('customerGroups?filter[1][attribute]=customer_group_code&filter[1][eq]=UnitTest');

        $this->assertTrue(is_array($customerGroups));
        $this->assertEquals(1, count($customerGroups));
    }

    /**
     * @depends testGetCustomerGroupsFilter
     */
    public function testUpdateCustomerGroup()
    {
        $customerGroups = $this->get('customerGroups?filter[1][attribute]=customer_group_code&filter[1][eq]=UnitTest');

        self::$dataCustomerGroup['customer_group_code'] = 'UnitTest2';
        $customerGroup = $this->put('customerGroups/' . $customerGroups[0]['customer_group_id'], self::$dataCustomerGroup);
    }

    /**
     * @depends testGetCustomerGroupsFilter
     */
    public function testDeleteCustomerGroup()
    {
        $customerGroups = $this->get('customerGroups?filter[1][attribute]=customer_group_code&filter[1][eq]=UnitTest2');

        $this->delete('customerGroups/' . $customerGroups[0]['customer_group_id']);
    }
}
