<?php
class MageLine_Sellsy_Model_Products extends Mage_Core_Model_abstract{
    
    protected function _construct()
    {
        $this->_init('sellsy/products');
    }
    
}