<?php

class ApiExtension_Magento_Model_CatalogInventory_Api2_Stock_Item_Rest_Admin_V1 extends Mage_CatalogInventory_Model_Api2_Stock_Item_Rest_Admin_V1
{
    /**
     * Loads stock by product sku or stock item id
     *
     * @param int $id
     *
     * @return Mage_CatalogInventory_Model_Stock_Item
     */
    protected function _loadStockItemById($id)
    {
        if ($_productId = Mage::getModel('catalog/product')->getIdBySku($id)) {
            $_product = Mage::getModel('catalog/product')->load($_productId);

            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);
        } else {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->load($id);
        }

        /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
        if (!$stockItem->getId()) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }

        return $stockItem;
    }
}
