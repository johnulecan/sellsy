<?php
class MageLine_Sellsy_Model_Resource_Products extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('sellsy/products', 'id');
    }
}