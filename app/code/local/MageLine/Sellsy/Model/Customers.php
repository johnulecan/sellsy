<?php
class MageLine_Sellsy_Model_Customers extends Mage_Core_Model_Abstract
{
    
    protected function _construct()
    {
        $this->_init('sellsy/customers');
    }
}