<?php
class MageLine_Sellsy_Model_Resource_Payments extends Mage_Core_Model_Resource_Db_Abstract
{
    
    protected function _construct()
    {
        $this->_init('sellsy/payments', 'id');
    }
}
