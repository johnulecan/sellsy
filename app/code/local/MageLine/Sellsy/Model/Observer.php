<?php class MageLine_Sellsy_Model_Observer
{
    public function export_order($observer)
    {
        if(!Mage::helper('sellsy/data')->isEnabled())
            return $this;
        $order = $observer->getEvent()->getOrder();
        
        if(!$order)
            return $this;
        
        $response = Mage::getModel('sellsy/sellsy')->_createOrder($order);
        //Mage::log(print_r($response), null, 'sellsy.log', true);
        
    }
    
    public function export_invoice($observer)
    {
        if(!Mage::helper('sellsy/data')->isEnabled())
            return $this;
        if($this->_checkRoutes()){
            $_event = $observer->getEvent();
            $_invoice = $_event->getInvoice();
            $_order = Mage::getModel('sales/order')->loadByIncrementId($_invoice->getOrder()->getIncrementId());
     
                $response = Mage::getModel('sellsy/sellsy')->_createOrder($_order);
                $response = Mage::getModel('sellsy/sellsy')->_createOrder($_order, true, $_invoice->getId());          

        }
    }
    
    public function export_credit_memo($observer)
    {
        if(!Mage::helper('sellsy/data')->isEnabled())
            return $this;
        if($this->_checkRoutes()){
            $_event = $observer->getEvent();
            $credit_memo = $_event->getCreditmemo();
            $_order = $credit_memo->getOrder();
            $response = Mage::getModel('sellsy/sellsy')->_createCreditMemo($_order, $credit_memo);
        }
        else
            return $this;
    }
    
    private function _checkRoutes(){
        
        $request        = Mage::app()->getRequest();
        $moduleName     = $request->getModuleName();
        $controllerName = $request->getControllerName();
        $actionName     = $request->getActionName();
        
        if ($moduleName != 'customer' && $controllerName != 'accout' && $actionName != 'login')
            return true;
        else
            return false;
    }
    
}

