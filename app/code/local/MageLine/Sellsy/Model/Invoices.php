<?php
class MageLine_Sellsy_Model_Invoices extends Mage_Core_Model_Abstract
{
    
    protected function _construct()
    {
        $this->_init('sellsy/invoices');
    }
}