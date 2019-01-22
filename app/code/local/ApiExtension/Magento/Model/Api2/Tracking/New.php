<?php
//
//#CREATE
//
//// $order_id = Order ID
//$_order = Mage::getModel('sales/order')->load($order_id);
//
//if ($_order->canShip()) {
//    $shipmentId = Mage::getModel('sales/order_shipment_api')->create($_order->getIncrementId(), $itemsarray, 'your_comment', false, 1);
//    echo $shipmentId;   // Outputs Shipment Increment Number
//    $trackmodel = Mage::getModel('sales/order_shipment_api')
//        ->addTrack($shipmentId, 'your_shipping_carrier_code', 'your_shipping_carrier_title', 'carrier_tracking_number');
//}
//
//#RETRIEVE
//$shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
//    ->setOrderFilter($order)
//    ->load();
//foreach ($shipmentCollection as $shipment) {
//    $shipment->getAllTracks();
//}
//
//foreach ($shipmentCollection as $shipment) {
//    foreach ($shipment->getAllTracks() as $tracknum) {
//        $tracknums[] = $tracknum->getNumber();
//    }
//}
