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
class ApiExtension_Magento_Model_Api2_Webhooks_Rest_Admin_V1 extends ApiExtension_Magento_Model_Api2_Webhooks
{
    const HOOK_NOT_AVAILABLE = 'Requested hook code is not found.';
    const INVALID_PARAMS = 'Hook code and url are required.';
    const URL_NOT_VALID = 'URL is not valid';

    /**
     * Gets webhook by ID
     */
    public function _retrieve()
    {
        $webhook = Mage::getModel('apiExtension/webhooks')->load($this->getRequest()->getParam('id'));

        if ($webhook->getId() === null) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return $webhook;
    }

    /**
     * Creates new webhook
     *
     * @param array $data
     *
     * @return string
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _create(array $data)
    {
        Mage::log($data, null, 'system.log', true);

        if (!isset($data['code']) || !isset($data['url'])) {
            $this->_critical(self::INVALID_PARAMS);
        }

        $availableHooks = Mage::helper('apiExtension')->getAvailableHooks();
        $webhook = Mage::getModel('apiExtension/webhooks');

        if (!isset($availableHooks[$data['code']])) {
            $this->_critical(self::HOOK_NOT_AVAILABLE);
        }

        if (filter_var($data['url'], FILTER_VALIDATE_URL) === false) {
            $this->_critical(self::URL_NOT_VALID);
        }

        if (!isset($data['description'])) {
            $data['description'] = $availableHooks[$data['code']];
        }

        try {
            $webhook->addData($data)->save();

        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Mage_Api2_Exception $e) {
            throw $e;
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }

        $this->_getLocation($webhook);
    }

    /**
     * Updated webhook by id
     *
     * @param array $data
     *
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _update(array $data)
    {
        $webhook = Mage::getModel('apiExtension/webhooks')->load($this->getRequest()->getParam('id'));

        if (!$webhook->getId()) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }
        $availableHooks = Mage::helper('apiExtension')->getAvailableHooks();

        if (!isset($availableHooks[$data['code']])) {
            $this->_critical(self::HOOK_NOT_AVAILABLE);
        }

        if (filter_var($data['url'], FILTER_VALIDATE_URL) === false) {
            $this->_critical(self::URL_NOT_VALID);
        }

        try {
            $webhook->addData($data)->save();
        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Mage_Api2_Exception $e) {
            throw $e;
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
    }

    /**
     * Gets collection of webhooks
     */
    public function _retrieveCollection()
    {
        $result = array();
        $collection = Mage::getModel('apiExtension/webhooks')->getCollection();

        $this->_applyCollectionModifiers($collection);

        foreach ($collection as $webhook) {
            $result[] = $webhook->getData();
        }

        return $result;
    }

    /**
     * Deletes webhook
     *
     * $webhooks = Mage::getModel('apiExtension/webhooks')->load($url, 'url');
     */
    public function _delete()
    {
        $password = $this->getRequest()->getParam('id');
        $webhooks = Mage::getModel('apiExtension/webhooks')->getCollection()->load();
        $deleteResource = 0;

        foreach ($webhooks as $webhook) {

            $array = explode('&', $webhook->getData('url'));

            if (!empty($array[2])) {
                $token = str_replace('password=', '', $array[2]);
                if ($token == $password) {
                    $deleteResource = $webhook;
                }
            }
        }

        if (!$deleteResource->getId()) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        $deleteResource->delete();
    }
}
