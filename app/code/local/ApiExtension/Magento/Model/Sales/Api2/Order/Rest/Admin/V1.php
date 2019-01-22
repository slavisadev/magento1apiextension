<?php

/**
 * API2 Abstract Resource methods:
 *
 * method string _create() _create(array $filteredData) creation of an entity
 * method void _multiCreate() _multiCreate(array $filteredData) processing and creation of a collection
 * method array _retrieve() retrieving an entity
 * method array _retrieveCollection() retrieving a collection
 * method void _update() _update(array $filteredData) update of an entity
 * method void _multiUpdate() _multiUpdate(array $filteredData) update of a collection
 * method void _delete() deletion of an entity
 * method void _multidelete() _multidelete(array $requestData) deletion of a collection
 */
class ApiExtension_Magento_Model_Sales_Api2_Order_Rest_Admin_V1 extends Mage_Sales_Model_Api2_Order_Rest_Admin_V1
{
    public function _retrieve()
    {
        $orderId = $this->getRequest()->getParam('id');
        $collection = $this->_getCollectionForSingleRetrieve($orderId);

        if ($this->_isPaymentMethodAllowed()) {
            $this->_addPaymentMethodInfo($collection);
        }
        if ($this->_isGiftMessageAllowed()) {
            $this->_addGiftMessageInfo($collection);
        }
        $this->_addTaxInfo($collection);

        $order = $collection->getItemById($orderId);

        if (!$order) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }
        $orderData = $order->getData();
        $addresses = $this->_getAddresses(array($orderId));
        $items = $this->_getItems(array($orderId));
        $comments = $this->_getComments(array($orderId));

        if ($addresses) {
            $orderData['addresses'] = $addresses[$orderId];
        }
        if ($items) {
            $orderData['order_items'] = $items[$orderId];
        }
        if ($comments) {
            $orderData['order_comments'] = $comments[$orderId];
        }

        return $orderData;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function _create(array $data)
    {



        $_order = Mage::getModel('sales/order')->loadByIncrementId(145000184);
        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
            ->setOrderFilter($_order)
            ->load();

        $return = array();

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

            $return[] =
                array(
                    'order_increment_id' => $shipment->getIncrementId(),
                    'protect_code' => $shipment->getProtectCode(),
                    'tracking_data' => $tracks
                );
        }

        Mage::log(json_encode($return), null, 'system.log');

        exit;

        //dump received data
        Mage::log($data, null, 'system.log');

        //prepare variables
        $__isGuest = $data['is_guest'];
        $__customerEmail = $data['customer_email'];
        $__orderItems = $data['order_items'];
        $__countryId = $data['country_id'];
        $__shippingMethod = $data['shipping_method_code'];
        $__shippingDescription = $data['shipping_description'];
        $__paymentMethod = $data['payment_method'];
        $__storeId = $data['store_id'];
        $__costData = array(
            'base_subtotal' => $data['base_subtotal'],
            'base_shipping_amount' => $data['base_shipping_amount'],
        );

        /** @var ApiExtension_Magento_Helper_Order $orderHelper */
        $orderHelper = Mage::helper('apiExtension/order');

        $checkoutObject = $orderHelper->getCheckoutObject($__isGuest);
        $quoteObject = $orderHelper->initiateQuote($checkoutObject, $__customerEmail, $__countryId, $__storeId);

        $orderHelper->handleOrderItems($__orderItems, $quoteObject, $this, self::RESOURCE_NOT_FOUND, $__storeId, $__shippingMethod, $__countryId);
        $orderHelper->handleShipping($quoteObject, $__shippingDescription, $__costData, $__shippingMethod);
        $orderHelper->handlePayment($__paymentMethod, $checkoutObject);

        /** @var Mage_Sales_Model_Service_Quote $service */
        $service = Mage::getModel('sales/service_quote', $quoteObject);
        $service->submitAll();

        /** @var Mage_Sales_Model_Order $order */
        $order = $service->getOrder();

        if ($orderHelper->updateMagentoOrder($order, $quoteObject)) {
            $quoteObject->save();
            $order->save();
        }
        if (isset($data['_order_comments'])) {
            $orderHelper->handleComments($data['_order_comments'], $order);
        }

        return $this->_getLocation($order);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function _update(array $data)
    {

        /** @var ApiExtension_Magento_Helper_Order $orderHelper */
        $orderHelper = Mage::helper('apiExtension/order');
        $orderId = $this->getRequest()->getParam('id');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        //$quote = $order->getQuote();
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter('entity_id', $order->getQuoteId())->getFirstItem();

        $quote->setStoreId($data['store_id']);

        $productModel = Mage::getModel('catalog/product');
        $productObj = $productModel->load($data['order_items'][0]['id']);

        // Create the item for the quote
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        //$quoteItem = Mage::getModel('sales/quote_item')
        $quoteItem = new Mage_Sales_Model_Quote_Item();
        $quoteItem->setProduct($productObj)
            ->setQuote($quote)
            ->setPrice($productObj->getPrice())
            ->setQty($data['order_items'][0]['qty'])
            ->calcRowTotal()
            ->save();

        // Convert the quote item to an order item for this order
        $orderItem = Mage::getModel('sales/convert_quote')
            ->itemToOrderItem($quoteItem)
            ->setOrderID($order->getId())
            ->save($orderId);

        $newSubTotal = $orderHelper->getSubTotal($data['items']);
        $newGrandTotal = $orderHelper->getGrandTotal($data['items'], $order, $data['discount_amount']);

        $order->setSubtotal($newSubTotal)->setBaseSubtotal($newSubTotal);
        if ($data['discount_amount'] != '') {
            $order->setDiscountAmount($data['discount_amount'])->setBaseDiscountAmout($data['discount_amount']);
        }
        $order->setGrandTotal($newGrandTotal)->setBaseGrandTotal($newGrandTotal);
        $order->save();

        $orderHelper->updateShipping($data['shipping_description'], $quote, $order);

        return $this->_getLocation($order);
    }
}
