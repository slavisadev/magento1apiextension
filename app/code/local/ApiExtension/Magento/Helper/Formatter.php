<?php

/**
 * @category    ApiExtension
 * @package     ApiExtension_Magento
 * @copyright   2016 ApiExtension.com
 * @license     http://license.apiExtension.com/  Unlimited Commercial License
 */
class ApiExtension_Magento_Helper_Formatter extends Mage_Core_Helper_Abstract
{
    /**
     * Transforms data according to passed object. For now supported types are:
     * - Order
     * - Product
     * - Customer
     *
     * @param $object
     *
     * @return array|mixed
     */
    public function transform($object)
    {
        $result = array();

        // double check
        if (is_null($object) || is_null($object->getId())) {
            return false;
        }

        $class = get_class($object);

        switch ($class) {
            case 'Mage_Sales_Model_Order':
                $result = $this->transformOrder($object);
                break;
            case 'Mage_Catalog_Model_Product':
                $result = $this->transformProduct($object);
                break;
            case 'Mage_Customer_Model_Customer':
                $result = $this->transformCustomer($object);
                break;
            case 'Mage_Catalog_Model_Category':
                $result = $this->transformCategory($object);
                break;
            case 'Mage_CatalogInventory_Model_Stock_Item':
                $result = $this->transformInventory($object);
                break;
        }

        return $result;
    }

    /**
     * Transforms order data
     *
     * @param Mage_Sales_Model_Order $object
     *
     * @return mixed
     */
    private function transformOrder(Mage_Sales_Model_Order $object)
    {
        $result = array();

        /** @var Mage_Sales_Model_Order $object */
        $result['id'] = $object->getId();
        $result['increment_id'] = $object->getIncrementId();
        $result['shipping_description'] = $object->getShippingMethod();
        $result['status'] = $object->getStatus();

        $result['store_id'] = $object->getStoreId();

        $result['base_currency_code'] = $object->getBaseCurrencyCode();
        $result['base_customer_balance_amount'] = $object->getBaseCustomerBalanceAmount();
        $result['base_discount_amount'] = $object->getBaseDiscountAmount();
        $result['base_grand_total'] = $object->getBaseGrandTotal();
        $result['base_shipping_discount_amount'] = $object->getShippingDiscountAmount();
        $result['base_shipping_tax_amount'] = $object->getShippingTaxAmount();
        $result['base_subtotal'] = $object->getBaseSubtotal();
        $result['base_subtotal_incl_tax'] = $object->getBaseSubtotalInclTax();

        $result['base_total_paid'] = $object->getBaseTotalPaid();
        $result['country_id'] = $object->getCountryId();
        $result['coupon_code'] = $object->getCouponCode();
        $result['customer_balance_amount'] = $object->getCustomerBalanceAmount();
        $result['customer_id'] = $object->getCustomerId();
        $result['gift_message_body'] = $object->getGiftMessageBody();
        $result['payment_method'] = $object->getPayment()->getMethod();
        $result['subtotal'] = $object->getSubtotal();

        $result['addresses'] = array();

        $billingAddress = $object->getBillingAddress();
        $street = $billingAddress->getStreet();

        $result['addresses'][] = array(
            'city' => $billingAddress->getCity(),
            'firstname' => $billingAddress->getFirstname(),
            'lastname' => $billingAddress->getLastname(),
            'region' => $billingAddress->getRegion(),
            'postcode' => $billingAddress->getPostcode(),
            'country_id' => $billingAddress->getCountryId(),
            'telephone' => $billingAddress->getTelephone(),
            'company' => $billingAddress->getCompany(),
            'prefix' => $billingAddress->getPrefix(),
            'middlename' => $billingAddress->getMiddlename(),
            'fax' => $billingAddress->getFax(),
            'street' => $street[0]
        );

        $shippingAddress = $object->getShippingAddress();
        $street = $shippingAddress->getStreet();

        $result['addresses'][] = array(
            'city' => $shippingAddress->getCity(),
            'firstname' => $shippingAddress->getFirstname(),
            'lastname' => $shippingAddress->getLastname(),
            'region' => $shippingAddress->getRegion(),
            'postcode' => $shippingAddress->getPostcode(),
            'country_id' => $shippingAddress->getCountryId(),
            'telephone' => $shippingAddress->getTelephone(),
            'company' => $shippingAddress->getCompany(),
            'prefix' => $shippingAddress->getPrefix(),
            'middlename' => $shippingAddress->getMiddlename(),
            'fax' => $shippingAddress->getFax(),
            'street' => $street[0]
        );

        return $result;
    }

    public function hasParent($productId)
    {
        return count(Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId)) >= 1;
    }

    /**
     * Transforms product data
     *
     * @param Mage_Catalog_Model_Product $object
     *
     * @return mixed
     */
    private function transformProduct($object)
    {
        $result = array(
            'id' => $object->getId(),
            'entity_id' => $object->getId(),
            'attribute_set_id' => $object->getAttributeSetId(),
            'sku' => $object->getSku(),
            'name' => $object->getName(),
            'type_id' => $object->getTypeId(),
            'visibility' => $object->getVisibility(),
            'price' => $object->getPrice(),
            'status' => $object->getStatus(),
            'weight' => $object->getWeight(),
            'has_parent' => $this->hasParent($object->getId()),
            'tax_class_id' => $object->getTaxClassId(),
            'description' => $object->getDescription(),
            'short_description' => $object->getShortDescription()
        );

        $media = $object->getMediaGalleryImages();

        foreach ($media->getItems() as $item) {

            $result['media'][] =
                array(
                    'value_id' => $item->getValueId(),
                    'file' => $item->getFile(),
                    'product_id' => $item->getProductId(),
                    'label' => $item->getLabel(),
                    'position' => $item->getPosition(),
                    'disabled' => $item->getIsDisabled(),
                    'label_default' => $item->getLabelDefault(),
                    'position_default' => $item->getPositionDefault(),
                    'disabled_default' => $item->getDisabledDefault(),
                    'url' => $item->getUrl(),
                    'id' => $item->getId(),
                    'path' => $item->getPath(),
                );
        }

        /** @var Mage_Sales_Model_Order_Creditmemo_Item $stock */
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($object);

        $result['stock_data'] = array(
            'is_in_stock' => (int)$object->isInStock(),
            'qty' => $stock->getQty(),
            'backorders' => $stock->getBackorders(),
            'stock_id' => $stock->getId(),
            'enable_qty_increments' => (int)$stock->getEnableQtyIncrements(),
            'is_qty_decimal' => $stock->getIsQtyDecimal(),
            'manage_stock' => $stock->getManageStock(),
            'max_sale_qty' => $stock->getMaxSaleQty(),
            'min_qty' => $stock->getMinQty(),
            'notify_stock_qty' => $stock->getNotifyStockQty(),
            'min_sale_qty' => $stock->getMinSaleQty(),
            'product_id' => $object->getId(),
            'item_id' => $stock->getItemId(),
        );

        return $result;
    }

    /**
     * Transforms customer data
     *
     * @param $object
     *
     * @return array
     */
    private function transformCustomer($object)
    {
        $result = array();

        /** @var Mage_Customer_Model_Customer $object */
        $result['id'] = $object->getId();
        $result['email'] = $object->getEmail();
        $result['firstname'] = $object->getFirstname();
        $result['lastname'] = $object->getLastname();
        $result['gender'] = $object->getGender();
        $result['middlename'] = $object->getMiddlename();
        $result['prefix'] = $object->getPrefix();
        $result['suffix'] = $object->getSuffix();
        $result['dob'] = $object->getDob();
        $result['taxvat'] = $object->getTaxvat();
        $result['store_id'] = $object->getStoreId(); // ID
        $result['website_id'] = $object->getWebsiteId();
        $result['group_id'] = $object->getGroupId();
        $result['tax_class_id'] = $object->getTaxClassId();

        $customerDefaultBillingAddressId = $object->getDefaultBilling();
        $customerDefaultShippingAddressId = $object->getDefaultShipping();

        $addressDefaultBilling = Mage::getModel('customer/address')->load($customerDefaultBillingAddressId);
        $addressDefaultShipping = Mage::getModel('customer/address')->load($customerDefaultShippingAddressId);

        $result['addresses'] = array();

        $street = $addressDefaultBilling->getStreet();

        $result['addresses'][] = array(
            'city' => $addressDefaultBilling->getCity(),
            'firstname' => $addressDefaultBilling->getFirstname(),
            'lastname' => $addressDefaultBilling->getLastname(),
            'region' => $addressDefaultBilling->getRegion(),
            'postcode' => $addressDefaultBilling->getPostcode(),
            'country_id' => $addressDefaultBilling->getCountryId(),
            'telephone' => $addressDefaultBilling->getTelephone(),
            'company' => $addressDefaultBilling->getCompany(),
            'prefix' => $addressDefaultBilling->getPrefix(),
            'middlename' => $addressDefaultBilling->getMiddlename(),
            'fax' => $addressDefaultBilling->getFax(),
            'vat_id' => $addressDefaultBilling->getVatId(),
            'is_default_billing' => $addressDefaultBilling->getId() == $customerDefaultBillingAddressId,
            'is_default_shipping' => $addressDefaultBilling->getId() == $customerDefaultShippingAddressId,
            'street' => $street[0]
        );

        $street = $addressDefaultBilling->getStreet();

        $result['addresses'][] = array(
            'city' => $addressDefaultShipping->getCity(),
            'firstname' => $addressDefaultShipping->getFirstname(),
            'lastname' => $addressDefaultShipping->getLastname(),
            'region' => $addressDefaultShipping->getRegion(),
            'postcode' => $addressDefaultShipping->getPostcode(),
            'country_id' => $addressDefaultShipping->getCountryId(),
            'telephone' => $addressDefaultShipping->getTelephone(),
            'company' => $addressDefaultShipping->getCompany(),
            'prefix' => $addressDefaultShipping->getPrefix(),
            'middlename' => $addressDefaultShipping->getMiddlename(),
            'fax' => $addressDefaultShipping->getFax(),
            'vat_id' => $addressDefaultBilling->getVatId(),
            'is_default_billing' => $addressDefaultShipping->getId() == $customerDefaultBillingAddressId,
            'is_default_shipping' => $addressDefaultShipping->getId() == $customerDefaultShippingAddressId,
            'street' => $street[0]
        );

        return $result;
    }

    /**
     * @param $array
     * @return string
     */
    public function toString($array)
    {
        return implode(', ', $array);
    }

    /**
     * Transforms category data
     *
     * @param $object
     *
     * @return array
     */
    private function transformCategory($object)
    {
        $availableSortBy = $this->toString($object->getAvailableSortBy());

        /** @var Mage_Catalog_Model_Category $object */
        $result = array(
            'id' => $object->getId(),
            'name' => $object->getName(),
            'description' => $object->getDescription(),
            'is_anchor' => $object->getIsAnchor(),
            'include_in_menu' => $object->getIncludeInMenu(),
            'parent_id' => $object->getParentId(),
            'path' => $object->getPath(),
            'is_active' => $object->getIsActive(),
            'url_path' => $object->getUrlPath(),
            'meta_keywords' => $object->getMetaKeywords(),
            'meta_description' => $object->getMetaDescription(),
            'available_sort_by' => $availableSortBy,
            'meta_title' => $object->getMetaTitle(),
            'display_mode' => $object->getDisplayMode(),
            'custom_design' => $object->getCustomDesign(),
            'page_layout' => $object->getPageLayout()
        );

        return $result;
    }

    /**
     * Transforms category data
     *
     * @param $object
     *
     * @return array
     */
    private function transformInventory($object)
    {
        $result = array();

        /** @var Mage_Catalog_Model_Category $object */
        $result['id'] = $object->getProductId();
        $params['qty'] = $object->getQty();
        $params['qty_change'] = $object->getQty() - $object->getOrigData('qty');

        return $result;
    }
}
