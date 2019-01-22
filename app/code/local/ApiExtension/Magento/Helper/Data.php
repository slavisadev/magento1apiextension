<?php

/**
 * @category    ApiExtension
 * @package     ApiExtension_Magento
 * @copyright   2016 ApiExtension.com
 * @license     http://license.apiExtension.com/  Unlimited Commercial License
 */
class ApiExtension_Magento_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MODULE_NAME = 'ApiExtension_Magento';

    /**
     * Checks if enabled webhooks in configurations
     *
     * @return string
     */
    public function isEnabledWebHooks()
    {
        return Mage::getStoreConfigFlag('apiExtension/webhook/enabled');
    }

    /**
     * Gets stores options
     *
     * @return array
     */
    public function getStoreOptions()
    {
        $result = array();

        /** @var Mage_Core_Model_Website $website */
        foreach (Mage::app()->getWebsites() as $website) {
            /** @var Mage_Core_Model_Store_Group $group */
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $result[$store['store_id']] = $store['name'];
                }
            }
        }

        return $result;
    }

    /**
     * Gets customer groups
     *
     * @return array
     */
    public function getCustomerGroups()
    {
        $result = array();
        $customerGroups = Mage::getModel('customer/group')->getCollection();

        foreach ($customerGroups as $type) {
            $result[$type->getCustomerGroupId()] = $type->getCustomerGroupCode();
        }

        return $result;
    }

    /**
     * Gets list of all countries
     *
     * @return array
     */
    public function getCountriesOptions()
    {
        $result = array();
        $countries = Mage::getResourceModel('directory/country_collection')
            ->loadData()
            ->toOptionArray();

        foreach ($countries as $country) {
            if (!$country['value']) {
                continue;
            }

            $result[$country['value']] = $country['label'];
        }

        return $result;
    }

    /**
     * Gets list of all regions for all countries
     *
     * @return array
     */
    public function getRegionOptions()
    {
        $result = array();
        $regions = Mage::getResourceModel('directory/region_collection')->load()->toOptionArray();

        foreach ($regions as $region) {
            if (!$region['value']) {
                continue;
            }

            $result[$region['value']] = $region['label'];
        }

        return $result;
    }

    /**
     * Gets website
     *
     * @return array
     */
    public function getWebsitesOptions($includeAll = false)
    {
        $result = array();

        if($includeAll) {
            $result[0] = 'All Websites';
        }

        /** @var Mage_Core_Model_Website $website */
        foreach (Mage::app()->getWebsites() as $website) {
            $result[$website->getId()] = $website->getName();
        }

        return $result;
    }

    /**
     * Curl data and return body
     *
     * @param      $data
     * @param      $url
     * @param null $token
     *
     * @return stdClass $output
     */
    public function proxy($data, $url, $token = null)
    {
        $output = new stdClass();
        $ch = curl_init();
        $body = json_encode($data);

        if (!is_null($token)) {
            $url .= "?token=$token";
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body)
        ));

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // 2 minutes to connect
        //curl_setopt($ch, CURLOPT_TIMEOUT, 60 * 4); // 8 minutes to fetch the response
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // execute
        try {

            $response = curl_exec($ch);
            $output->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // handle response
            $result = explode("\r\n\r\n", $response, 2);
            if (count($result) == 2) {
                $output->header = $result[0];
                $output->body = $result[1];
            } else {
                $output->body = $this->__('Unexpected response');
            }

            Mage::helper('apiExtension')->log('Webhook execution: ' . $output);
        } catch (Exception $e) {
            Mage::helper('apiExtension')->log('Webhook execution error: ' . $e->getMessage());
        }

        return $output;
    }

    /**
     * Post via cURL
     *
     * @param $data
     * @param $url
     * @param $token
     *
     * @return string
     */
    public function postInsertData($data, $url, $token)
    {
        if (!is_null($token)) {
            $url .= "?token=$token";
        }

        $client = new Zend_Http_Client($url);

        $client->setMethod(Zend_Http_Client::POST);
        $client->setHeaders('Content-type', 'application/json');
        $client->setHeaders('Accept', 'application/json');

        $dataJson = Mage::helper('core')->jsonEncode($data);

        $client->setParameterPost($dataJson);

        $response = $client->request(Zend_Http_Client::POST);

        return $response;
    }

    /**
     * Returns all hook codes available in extension, including description
     *
     * @return array
     */
    public function getAvailableHooks()
    {
        return array(
            'cataloginventory_stock_item_save_commit_after' => $this->__('Triggers on after inventory save.'),
            'sales_order_save_commit_after' => $this->__('Triggers on after order save.'),
            'customer_save_commit_after' => $this->__('Triggers on after customer save.'),
            'catalog_product_save_commit_after' => $this->__('Triggers on after product save.'),
            'customer_group_save_commit_after' => $this->__('Triggers on after customer group save.'),
            'catalog_category_save_commit_after' => $this->__('Triggers on after catalog category save.')
        );
    }

    /**
     * Logs data to var/log/apiExtension.log
     *
     * @param $log
     */
    public function log($log)
    {
        Mage::log(print_r($log, true), null, 'apiExtension.log');
    }
}
