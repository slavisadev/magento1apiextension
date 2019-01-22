<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_OrderStatusesTest extends ApiExtension_Magento_Base
{
    public function testOrderStatuses()
    {
        global $orderId;
        $attributes = $this->get('/orders/' . $orderId . '/statuses');

        $this->assertTrue(is_array($attributes));
    }

    public function testOrderStatusesNotFound()
    {
        $attributes = $this->get('/orders/5/statuses');

        $this->assertTrue(is_array($attributes));
    }
}
