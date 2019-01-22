<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_WebhooksTest extends ApiExtension_Magento_Base
{
    protected static $dataHook = array(
        'code' => 'sales_order_save_after',
        'active' => '1',
        'url' => 'http://example.com/callback.php',
        'token' => 'ug53c8p1q0szIl4dkA3xFM7Bi4esJj5Y',
        'data' => '{data:test}'
    );

    protected static $dataHookFake = array(
        'code' => 'sales_order_after_unit_test_fake',
        'active' => '1',
        'url' => 'http://example.com/callback.php',
    );

    public function testRegisteredHooks()
    {
        $webhooks = $this->get('webhooks');

        $this->assertTrue(is_array($webhooks));
    }

    public function testHookCreation()
    {
        $webhooks = $this->post('webhooks', self::$dataHook);

        $this->assertNull($webhooks);
    }

    /**
     * @expectedException Exception
     */
    public function testNotAvailableHookCreation()
    {
        $webhooks = $this->post('webhooks', self::$dataHookFake);

        $this->assertTrue(is_array($webhooks));
    }

    public function testHookFilterAndRetrieve()
    {
        $webhooks = $this->get('webhooks?filter[1][attribute]=code&filter[1][eq]=' . self::$dataHook['code'] . '&filter[2][attribute]=url&filter[2][eq]=' . self::$dataHook['url']);

        $result = $this->get('webhooks/' . $webhooks[0]['id']);

        $this->assertEquals(self::$dataHook['code'], $result['code']);
        $this->assertEquals(self::$dataHook['data'], $result['data']);
        $this->assertEquals(self::$dataHook['token'], $result['token']);
        $this->assertEquals(self::$dataHook['url'], $result['url']);
    }

    public function testUpdateWebhook()
    {
        $webhooks = $this->get('webhooks?filter[1][attribute]=code&filter[1][eq]=' . self::$dataHook['code'] . '&filter[2][attribute]=url&filter[2][eq]=' . self::$dataHook['url']);

        self::$dataHook['data'] = 'UpdatedData';

        $result = $this->put('webhooks/' . $webhooks[0]['id'], self::$dataHook);

        $this->assertNull($result);
    }

    public function testHookFilterAndDelete()
    {
        $webhooks = $this->get('webhooks?filter[1][attribute]=code&filter[1][eq]=' . self::$dataHook['code'] . '&filter[2][attribute]=url&filter[2][eq]=' . self::$dataHook['url']);

        $result = $this->delete('webhooks/' . $webhooks[0]['id']);

        $this->assertNull($result);
    }
}
