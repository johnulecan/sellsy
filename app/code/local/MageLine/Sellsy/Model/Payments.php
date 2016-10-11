<?php
class MageLine_Sellsy_Model_Payments extends Mage_Core_Model_Abstract
{
    
    protected function _construct()
    {
        $this->_init('sellsy/payments');
    }
}