<?php

/**
 * Observer to handle event
 * Sends JSON data to URL specified in extensions admin settings
 */
class ApiExtension_Magento_Model_Observer
{
    /**
     * Used to ensure the event is not fired multiple times
     *
     * @var bool
     */
    private $processFlag = false;

    /**
     * @var ApiExtension_Magento_Helper_Data
     */
    private $helper;

    /**
     * @var ApiExtension_Magento_Helper_Formatter
     */
    private $formatter;

    /**
     * ApiExtension_Magento_Model_Observer constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('apiExtension');
        $this->formatter = Mage::helper('apiExtension/formatter');
    }

    /**
     * Posts order
     *
     * @param Varien_Event_Observer $observer
     * @return ApiExtension_Magento_Model_Observer
     */
    public function postOrder(Varien_Event_Observer $observer)
    {
        /** @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();
        $eventName = $observer->getEvent()->getName();

        if (!is_null($order->getStatus())) {
            $this->execute($eventName, $order);
        }
    }

    /**
     * Posts product
     *
     * @param Varien_Event_Observer $observer
     * @return ApiExtension_Magento_Model_Observer
     */
    public function postProduct(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();
        $eventName = $observer->getEvent()->getName();

        $this->execute($eventName, $product);
    }

    /**
     * Posts customer
     *
     * @param Varien_Event_Observer $observer
     * @return ApiExtension_Magento_Model_Observer
     */
    public function postCustomer(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        $eventName = $observer->getEvent()->getName();

        $this->execute($eventName, $customer);
    }

    /**
     * Posts customer group
     *
     * @param Varien_Event_Observer $observer
     * @return ApiExtension_Magento_Model_Observer
     */
    public function postCustomerGroup(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customerGroup = $observer->getEvent()->getCustomerGroup();
        $eventName = $observer->getEvent()->getName();

        $this->execute($eventName, $customerGroup);
    }

    /**
     * Posts category
     *
     * @param Varien_Event_Observer $observer
     * @return ApiExtension_Magento_Model_Observer
     */
    public function postCategory(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $category = $observer->getEvent()->getCategory();
        $eventName = $observer->getEvent()->getName();

        $this->execute($eventName, $category);
    }

    /**
     * Posts inventory
     *
     * @param Varien_Event_Observer $observer
     * @return ApiExtension_Magento_Model_Observer
     */
    public function postInventory(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $inventory = $observer->getEvent()->getItem();
        $eventName = $observer->getEvent()->getName();

        $this->execute($eventName, $inventory);
    }

    /**
     * @param $eventName
     * @param $eventData
     * @return bool
     */
    private function execute($eventName, $eventData)
    {
        if ($this->processFlag || !$this->helper->isEnabledWebHooks()) {
            return false;
        }

        $this->helper->log('ApiExtension Webhook Preparation for Event: ' . $eventName);

        $collection = Mage::getModel('apiExtension/webhooks')
            ->getCollection()
            ->addFieldToFilter('code', $eventName)
            ->addFieldToFilter('active', '1');

        if ($collection->getSize() === 0) {
            return false;
        }

        $eventData = $this->formatter->transform($eventData);

        foreach ($collection as $webhook) {
            $eventData['data'] = $webhook->getData('data');
            $eventData['webhook'] = $webhook->getData();
            $eventData['info'] = array(
                'base_url' => Mage::getBaseUrl(),
                'server_ip' => $_SERVER['SERVER_ADDR'],
                'time' => time()
            );

            $response = $this->helper->proxy($eventData, $webhook->getUrl(), $webhook->getToken());

            $this->helper->log('ApiExtension Webhook: Sent, Event: ' . $eventName .
                ' URL: ' . $webhook->getUrl() . ' Status: ' . $response->status . ' Response: ' . $response->body);
        }

        $this->processFlag = true;

        return $collection;
    }

}
