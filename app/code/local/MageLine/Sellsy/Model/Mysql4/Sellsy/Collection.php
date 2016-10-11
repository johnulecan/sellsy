<?php
class MageLine_Sellsy_Model_Mysql4_Sellsy_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->init('sellsy/sellsy');
    }
}