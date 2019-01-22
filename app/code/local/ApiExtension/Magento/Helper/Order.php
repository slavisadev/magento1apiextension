<?php

class ApiExtension_Magento_Helper_Order extends Mage_Core_Model_Abstract
{

    /**
     * @param array $productsArray
     *
     * @return int|string
     */
    public function getSubTotal($productsArray)
    {
        $subTotal = 0;
        foreach ($productsArray as $item) {
            $productModel = Mage::getModel('catalog/product');
            $productObj = $productModel->load($item['id']);
            $price = $productObj->getPrice();
            $priceTimesQty = (int)$price * $item['qty'];

            $subTotal += (int)$priceTimesQty;
        }
        return $subTotal;
    }

    /**
     * @param                        $productsArray
     * @param Mage_Sales_Model_Order $order
     *
     * @return int|string
     */
    public function getGrandTotal($productsArray, $order, $discount)
    {
        $currentGrandTotal = $order->getGrandTotal();
        $discountAmount = $this->getDiscount($discount);
        return $this->getSubTotal($productsArray) + $currentGrandTotal - $discountAmount;
    }

    /**
     * @param $discount
     *
     * @return int
     */
    public function getDiscount($discount)
    {
        if ($discount != '') {
            return $discount;
        }

        return 0;
    }

    /**
     * @param string $shippingMethod
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Order $order
     */
    public function updateShipping($shippingMethod, $quote, $order)
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setShippingMethod($shippingMethod);
        $shippingAddress->setShippingDescription('some desc');

        $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
        $quote->collectTotals();

        if ($this->updateMagentoOrder($order, $quote)) {

            // here's where I check if we successfully updated the authorized
            // amount at the payment gateway, before saving anything
            // wrapping the payment update and save in a try-catch
            $quote->save();
            $order->save();
        }
    }

    /**
     * Updates a Magento order based on quote changes
     * will not save anything, up to the caller.
     * deleting items not supported.
     *
     * @param  $order Mage_Sales_Model_Order
     * @param  $quote Mage_Sales_Model_Quote
     *
     * @return bool
     */
    public function updateMagentoOrder($order, $quote)
    {
        if (!$order instanceof Mage_Sales_Model_Order || !$quote instanceof Mage_Sales_Model_Quote) {
            return false;
        }

        try {
            $converter = Mage::getSingleton('sales/convert_quote');
            $converter->toOrder($quote, $order);

            foreach ($quote->getAllItems() as $quoteItem) {

                $orderItem = $converter->itemToOrderItem($quoteItem);
                $quoteItemId = $quoteItem->getId();
                $origOrderItem = empty($quoteItemId) ? null : $order->getItemByQuoteItemId($quoteItemId);

                if ($origOrderItem) {
                    $origOrderItem->addData($orderItem->getData());
                } else {
                    if ($quoteItem->getParentItem()) {
                        $orderItem->setParentItem(
                            $order->getItemByQuoteItemId($quoteItem->getParentItem()->getId())
                        );
                        $orderItem->setParentItemId($quoteItem->getParentItemId());
                    }
                    $order->addItem($orderItem);
                }
            }

            if ($shippingAddress = $quote->getShippingAddress()) {
                $converter->addressToOrder($shippingAddress, $order);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;
    }

    /**
     * @param $requestUri
     *
     * @return array
     */
    public function setRequestParams($requestUri)
    {
        $requestUriArray = explode('?', $requestUri);
        parse_str($requestUriArray[1], $get_array);
        $requestParams = array();
        foreach ($get_array as $item => $value) {
            $requestParams[$item] = $value;
        }
        return $requestParams;
    }

    /**
     * Return all available and active payment methods
     *
     * @return array
     */
    public function getActivePaymentMethods()
    {
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        $methods = array();
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/' . $paymentCode . '/title');
            $methods[$paymentCode] = array(
                'label' => $paymentTitle,
                'value' => $paymentCode,
            );
        }
        return $methods;
    }

    /**
     * @return array
     */
    public function getActiveShippingMethods()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

        $options = array();

        foreach ($methods as $_code => $_method) {
            if (!$_title = Mage::getStoreConfig('carriers/$_code/title'))
                $_title = $_code;

            $options[] = array('value' => $_code, 'label' => $_title . ' ($_code)');
        }

        return $options;
    }

    /**
     * @param $authType
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function initCheckout($authType)
    {
        $checkoutOnePage = new Mage_Checkout_Model_Type_Onepage();
        $checkoutOnePage->saveCheckoutMethod($authType);
        return $checkoutOnePage;
    }

    /**
     * @param $productId
     * @return Mage_Catalog_Model_Product
     */
    public function loadProduct($productId)
    {
        /** @var Mage_Catalog_Model_Product $productModel */
        $productModel = Mage::getModel('catalog/product');
        $productObj = $productModel->load($productId);
        return $productObj;
    }

    /**
     * @param $customerEmail
     * @param $quoteObject
     * @param $storeId
     */
    public function loadCustomer($customerEmail, &$quoteObject, $storeId)
    {
        /** @var Mage_Customer_Model_Customer $customerObj */
        $customerObj = Mage::getModel('customer/customer');
        $customerObj->setWebsiteId($storeId);
        $customerObj->loadByEmail($customerEmail);
        $quoteObject->assignCustomer($customerObj);
    }

    /**
     * @param Mage_Catalog_Model_Product $productObject
     * @param Mage_Sales_Model_Quote $quoteObject
     * @param int|string $qty
     * @param int|string $storeId
     * @throws Mage_Api2_Exception
     */
    public function storeProductItem($productObject, $quoteObject, $qty, $storeId, $shippingMethod, $countryId)
    {
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        $quoteItem = Mage::getModel('sales/quote_item');
        $quoteItem->setProduct($productObject);
        $quoteItem->setQuote($quoteObject);

        if ($this->getProductQty($productObject) < $qty) {
            throw new Mage_Api2_Exception('Not enough quantity for product id: ' . $productObject->getId(), 404);
        }

        $quoteItem->setQty($qty);
        $quoteItem->setStoreId($storeId);

        $quoteObject->addItem($quoteItem);
        $quoteObject->setStoreId($storeId);

        $quoteObject->save();
    }

    /**
     * @param Mage_Catalog_Model_Product $productObject
     * @return int
     */
    public function getProductQty($productObject)
    {
        $model = Mage::getModel('catalog/product');
        $_product = $model->load($productObject->getId());
        $stocklevel = (int)Mage::getModel('cataloginventory/stock_item')
            ->loadByProduct($_product)->getQty();
        return $stocklevel;
    }

    /**
     * @param $paymentMethod
     * @param Mage_Checkout_Model_Type_Onepage $checkoutOnePage
     */
    public function handlePayment($paymentMethod, &$checkoutOnePage)
    {
        $checkoutOnePage->savePayment(
            array(
                'method' => $paymentMethod
            )
        );
    }

    /**
     * @param $orderComments
     * @param Mage_Sales_Model_Order $order
     */
    public function handleComments($orderComments, $order)
    {
        foreach ($orderComments as $order_comment) {
            $order->addStatusHistoryComment($order_comment['text']);
            $order->save();
        }
    }

    /**
     * @param $__isGuest
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getCheckoutObject($__isGuest)
    {
        ($__isGuest == true) ? $type = 'guest' : $type = 'custoemr';
        return $this->initCheckout($type);
    }

    /**
     * @param $checkoutObject
     * @param $__customerEmail
     * @param $__countryId
     * @param $__storeId
     *
     * @return Mage_Sales_Model_Quote
     */
    public function initiateQuote($checkoutObject, $__customerEmail, $__countryId, $__storeId)
    {
        /** @var Mage_Sales_Model_Quote $quoteObject */
        $quoteObject = $checkoutObject->getQuote();
        $this->loadCustomer($__customerEmail, $quoteObject, $__storeId);
        $quoteObject->getBillingAddress()->setCountryId($__countryId);

        return $quoteObject;
    }

    /**
     * @param $orderItems
     * @param $quoteObject
     * @param $api
     * @param $message
     * @param $storeId
     * @param $shippingMethod
     * @param $countryId
     */
    public function handleOrderItems($orderItems, &$quoteObject, $api, $message, $storeId, $shippingMethod, $countryId)
    {
        if (!is_array($orderItems))
            return;

        foreach ($orderItems as $item) {

            /** @var Mage_Catalog_Model_Product $productObj */
            $productObj = Mage::getModel('catalog/product')
                ->loadByAttribute('sku', $item['sku']);

            if ($productObj->getId() === null) {
                $api->_critical($message);
            }
            $this->storeProductItem($productObj, $quoteObject, $item['qty_ordered'], $storeId, $shippingMethod, $countryId);
        }
    }

    /**
     * @param $quoteObject
     * @param $shippingDescription
     * @param $costData
     * @param $shippingMethod
     */
    public function handleShipping(&$quoteObject, $shippingDescription, $costData, $shippingMethod)
    {
        /** @var Mage_Sales_Model_Quote $quoteObject */
        $shippingAddress = $quoteObject->getShippingAddress();
        $shippingAddress->setShippingDescription($shippingDescription);
        $quoteObject->setShippingAmount($costData['base_shipping_amount']);
        $shippingAddress->setShippingMethod($shippingMethod);
        $quoteObject->setBaseSubtotal($costData['base_subtotal']);
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
        $quoteObject->collectTotals();
    }
}
