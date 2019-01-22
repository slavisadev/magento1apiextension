<?php

require_once 'BaseTest.php';

class ApiExtension_Magento_InvoicesTest extends ApiExtension_Magento_Base
{
    public function testGetInvoices()
    {
        $invoices = $this->get('invoices');

        $this->assertTrue(is_array($invoices));
        $this->assertFalse(empty($invoices));
    }
}
