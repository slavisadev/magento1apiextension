<?php

class ApiExtension_Magento_Model_Api2_OrderStatuses_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_OrderStatuses
{
    /**
     * Gets list of order statuses by order id|increment_id
     * @return array
     */
    public function _retrieveCollection()
    {
        $result = array();

        $orderId = $this->getRequest()->getParam('id');

        $orderObject = $this->getAppropriateOrder($orderId, 'entity_id');

        if ($orderObject->getId()) {
            $result = $this->filterStatusesForOrder($orderObject);
        } else {
            $orderObject = $this->getAppropriateOrder($orderId, 'increment_id');
            if ($orderObject->getId()) {
                $result = $this->filterStatusesForOrder($orderObject);
            }
        }

        if (empty($orderObject)) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param $id
     * @return Mage_Sales_Model_Order
     */
    public function getAppropriateOrder($id, $filterAttr)
    {
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter($filterAttr, $id);
        $this->_applyCollectionModifiers($collection);
        $order = $collection->getFirstItem();
        return $order;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function filterStatusesForOrder($order)
    {
        $result = array();
        $statuses = $order->getStatusHistoryCollection();
        foreach ($statuses as $status) {
            $result[] = array(
                'entity_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'status' => $status->getStatusLabel(),
                'created_at' => $status->getCreatedAt(),
                'entity_name' => $status->getEntityName(),
                'is_customer_notified' => $status->getIsCustomerNotified(),
                'is_visible_on_front' => $status->getIsVisibleOnFront(),
            );
        }
        return $result;
    }

    /**
     * Updates order status
     * @param array $data
     * @return mixed
     */
    public function _update(array $data)
    {

        $orderID = $this->getRequest()->getParam('id');
        $newStatus = $data['status'];

        $orderObject = $this->getAppropriateOrder($orderID, 'increment_id');

        if ($orderObject->getId() === null) {
            $orderObject = $this->getAppropriateOrder($orderID, 'entity_id');
            if ($orderObject->getId() === null) {
                $this->_critical(self::RESOURCE_NOT_FOUND);
            }
        }

        $orderObject->setData('state', $newStatus);
        $orderObject->setData('status', $newStatus);

        $history = Mage::getModel('sales/order_status_history')
            ->setOrder($orderObject)
            ->setStatus($newStatus)
            ->setComment('api comment')
            ->setEntityName($data['entity_name'])
            ->setIsCustomerNotified($data['is_customer_notified'])
            ->setIsVisibleOnFront($data['is_visible_on_front']);

        try {
            $history->save();
            $orderObject->save();
        } catch (Mage_Core_Exception $e) {
            $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
        }

        return $orderObject->getId();
    }
}
