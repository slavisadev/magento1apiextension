<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_OrdersTest extends ApiExtension_Magento_Base
{

    protected static $dataOrder = array(
        'customer_id' => '1',
        'order_items' => [
            [
                'id' => 3,
                'qty' => 12
            ]
        ],
        'payment_method' => 'checkmo',
        'shipping_description' => 'flatrate_flatrate',
        'country_id' => 'RS',
        'store_id' => '1',
        '_order_comments' => [
            ['text' => 'first comment 2'],
            ['text' => 'second comment 1'],
        ]
    );

    public function testGetOrders()
    {
        $orders = $this->get('orders');

        $this->assertTrue(is_array($orders));
        $this->assertFalse(empty($orders));
    }

    public function testGetOrderById()
    {
        $orderId = 10;
        $order = $this->get('orders/' . $orderId);

        $this->assertTrue(is_array($order));
        $this->assertTrue(array_key_exists('entity_id', $order));
    }

    public function testCreateOrder()
    {
        $order = $this->post('orders', self::$dataOrder);
        $this->assertTrue(is_array($order));

    }

    public function testUpdateOrder()
    {
        $orderId = 22;
        $order = $this->put('orders/' . $orderId, self::$dataOrder);
        //$this->assertTrue(is_array($order));

    }
//
//    public function testFilterAndDeleteOrder()
//    {
//        $order = $this->get('categories?filter[1][attribute]=name&filter[1][eq]=' . self::$dataOrder['name']);
//        $order = reset($order);
//
//        $result = $this->delete('categories/' . $order['entity_id']);
//
//        $this->assertNull($result);
//    }
}
