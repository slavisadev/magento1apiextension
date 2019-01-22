<?php

/**
 * API2 Abstract Resource methods:
 *
 * method string _create() _create(array $data) creation of an entity
 * method void _multiCreate() _multiCreate(array $data) processing and creation of a collection
 * method array _retrieve() retrieving an entity
 * method array _retrieveCollection() retrieving a collection
 * method void _update() _update(array $data) update of an entity
 * method void _multiUpdate() _multiUpdate(array $data) update of a collection
 * method void _delete() deletion of an entity
 * method void _multidelete() _multidelete(array $requestData) deletion of a collection
 */
class ApiExtension_Magento_Model_Api2_Tracking_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_Tracking
{
    /**
     * retrieve shipment data
     *
     * @return array
     */
    public function _retrieveCollection()
    {
        $_orders = Mage::getModel('sales/order')->getCollection();

        $shipmentData = array();

        foreach ($_orders as $_order) {

            $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
                ->setOrderFilter($_order)
                ->load();


            /** @var Mage_Sales_Model_Order_Shipment $shipment */
            foreach ($shipmentCollection as $shipment) {

                $tracks = array();

                /** @var Mage_Sales_Model_Order_Shipment_Track $trackNumber */
                foreach ($shipment->getAllTracks() as $trackNumber) {
                    $tracks[] = array(
                        'tracking_number' => $trackNumber->getNumber(),
                        'carrier_code' => $trackNumber->getCarrierCode()
                    );
                }

                $shipmentData[] =
                    array(
                        'order_increment_id' => $shipment->getIncrementId(),
                        'protect_code' => $shipment->getProtectCode(),
                        'tracking_data' => $tracks
                    );
            }
        }
        return $shipmentData;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function _create(array $data)
    {
        Mage::log($data, null, 'system.log');

        $_order = Mage::getModel('sales/order')->loadByIncrementId($data['order_increment_id']);

        if ($_order->canShip()) {
            $shipmentId = Mage::getModel('sales/order_shipment_api')->create($_order->getIncrementId(), [], 'your_comment', false, 1);

            /** @var Mage_Sales_Model_Order_Shipment_Api $trackModel */
            $trackModel = Mage::getModel('sales/order_shipment_api');
            $trackModel->addTrack($shipmentId, $data['carrier_code'], $data['carrier_name'], $data['tracking_number']);
        }

        return $this->_getLocation($_order);
    }
}
