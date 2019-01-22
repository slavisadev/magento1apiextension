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
class ApiExtension_Magento_Model_Api2_Invoices_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_Invoices
{
    const CANNOT_CREATE_INVOICE = 'Cannot do invoice for order';
    const NO_ITEMS_INVOICE = 'Cannot create an invoice without products';

    /**
     * Loads by invoice ID or increment ID
     */
    public function _retrieve()
    {
        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($this->getRequest()->getParam('id'));

        if ($invoice->getId() === null) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        $result = $invoice->getData();

        $result['order_increment_id'] = $invoice->getOrderIncrementId();
        $result['comments'] = array();
        $result['items'] = array();

        foreach ($invoice->getAllItems() as $item) {
            $result['items'][] = $item->getData();
        }

        foreach ($invoice->getCommentsCollection() as $comment) {
            $result['comments'][] = $comment->getData();
        }

        return $result;
    }

    /**
     * Gets list of invoices
     */
    public function _retrieveCollection()
    {
        $result = array();

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $collection */
        $collection = Mage::getResourceModel('sales/order_invoice_collection');

        $this->_applyCollectionModifiers($collection);

        foreach ($collection->getItems() as $invoice) {
            $result[$invoice->getId()] = $invoice->toArray();

            foreach ($invoice->getAllItems() as $item) {
                $result[$invoice->getId()]['items'] = $item->getData();
            }

            foreach ($invoice->getCommentsCollection() as $comment) {
                $result[$invoice->getId()]['comments'] = $comment->getData();
            }
        }

        return $result;
    }

    /**
     * Creates new invoice
     *
     * @param array $data
     * @return string
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _create(array $data)
    {
        if (isset($data['order_increment_id'])) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($data['order_increment_id']);
        } else {
            $order = Mage::getModel('sales/order')->load($data['order_id']);
        }

        // Check order existing
        if ($order->getId() === null) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        // Check invoice create availability
        if (!$order->canInvoice()) {
            $this->_critical(self::CANNOT_CREATE_INVOICE);
        }

        $items = isset($data['items']) ? $data['items'] : array();

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $order->prepareInvoice($items);

        if (!$invoice->getTotalQty()) {
            $this->_critical(self::NO_ITEMS_INVOICE);
        }

        $invoice->register();
        $invoice->setData($data);

        // @todo Check with NNpro if customer should be notified
        if (isset($data['comments'])) {

            if (!is_array($data['comments'])) {
                $data['comments'] = array($data['comments']);
            }

            foreach ($data['comments'] as $comment) {
                $invoice->addComment($comment, true);
            }
        }

        $invoice->getOrder()->setIsInProcess(true);

        try {
            Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

            // @todo Check with NNpro if customer should be notified
            // $invoice->sendEmail($email, $comment);

        } catch (Mage_Core_Exception $e) {
            $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
        }

        return $invoice->getIncrementId();
    }

}
