<?php
class MageLine_Sellsy_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getApiUrl(){
        return Mage::getStoreConfig('sellsy/settings/api_url',Mage::app()->getStore());
    }

    public function getUserToken(){
        return Mage::getStoreConfig('sellsy/settings/access_token',Mage::app()->getStore());
    }
    
    public function getUserSecret(){
        return Mage::getStoreConfig('sellsy/settings/token_secret',Mage::app()->getStore());
    }    
    
    public function getConsumerToken(){
        return Mage::getStoreConfig('sellsy/settings/consumer_key',Mage::app()->getStore());
    }    
    
    public function getConsumerSecret(){
        return Mage::getStoreConfig('sellsy/settings/consumer_secret',Mage::app()->getStore());
    }
    
    public function isEnabled(){
        return Mage::getStoreConfig('sellsy/settings/status',Mage::app()->getStore());
    }
    
    public function isTestMode(){
        return Mage::getStoreConfig('sellsy/settings/test_mode',Mage::app()->getStore());
    }
    
}
	 