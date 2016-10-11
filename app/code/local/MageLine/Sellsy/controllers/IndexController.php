<?php
class MageLine_Sellsy_IndexController extends Mage_Core_Controller_Front_Action
{
    const RESPONSE_STATUS_SUCCESS = 'success';
    
    public function testAction(){
           $request = array(
                'method' => 'Infos.getInfos',
                'params' => array(),
            );

        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        Zend_debug::dump($response);die;
    }

    public function getipAction()
    {
        echo 'Your IP is: '.$_SERVER['REMOTE_ADDR'];
        die;
    }
    public function parcelsAction(){
        Zend_debug::dump(Mage::helper('estimated_delivery')->getParcels());
    }
    
    public function carriersAction(){
        $methods = Mage::getModel('shipping/config')->getActiveCarriers();
        Zend_debug::dump($methods);
    }
    
    public function testproductAction(){
        
        $catalog = Mage::getModel('catalog/product')->getCollection()->addAttributeToSort('entity_id', 'asc');
        $catalog->getSelect()->limit(10);
        
        foreach($catalog as $product){
            if( !$this->_productExported( $product->getId() ) ){
                $product = Mage::getModel('catalog/product')->load($product->getId());
                $export_product = $this->_createProduct($product);
                
                $magento_product = Mage::getModel('sellsy/products');
                $magento_product->setMagentoId($product->getId());
                $magento_product->setSellsyId($export_product->response->item_id);
                $magento_product->setUpdatedAt(now());
                $magento_product->save();
                echo 'done exporting '.$product->getName().'<br/>';
            }else{
                echo 'product '.$product->getName() .'was already exported<br>';
            }
        }
        
        
    }
    
    public function test_clientAction(){
        
        $collection = Mage::getModel('customer/customer')->getCollection()
           ->addAttributeToSelect('firstname')
           ->addAttributeToSelect('lastname')
           ->addAttributeToSelect('email');
           
           $collection->getSelect()->limit(10);
        
        foreach ($collection as $item)
        {
           if(!$this->_customerExported){
            $new_client = $this->_createClient($item->getId());
            $this->_mapCustomer($item->getId(), $new_client->response->client_id);
           }
        }
       
        echo 'done';
    }
    
    public function test_taxesAction()
    {
        $taxes_collection = Mage::getModel('tax/class')->getCollection()->addFieldToFilter('class_type', 'PRODUCT')->load();
        //Zend_debug::dump($taxes_collection->getData());die;
        foreach($taxes_collection->getData() as $tax_data){
            if(!$this->_taxExported){
               //Zend_debug::dump(Mage::getSingleton('tax/calculation')->getRateRequest()->setProductClassId($tax_data['class_id']));die;
                $taxClasses  = Mage::helper("core")->jsonDecode( Mage::helper("tax")->getAllRatesByProductClass() );
                
                //Zend_debug::dump($taxClasses);die;
                $tax_data['value'] = $taxClasses['value_'.$tax_data['class_id']];
                //Zend_debug::dump($tax_data);die;
                $new_tax = $this->_createTax($tax_data);
                
                if($new_tax->response->success == self::RESPONSE_STATUS_SUCCESS)
                
                        $this->_mapTax($tax_data['class_id'], $new_tax->response->taxeid);
                    else{
                    Zend_debug::dump($tax_data);
                    
                    die;
                    }
            }
        }
        //Zend_debug::dump($taxes_collection->getData());
        echo 'done';
    }
    
    public function get_taxAction(){
        
        Mage::getModel('sellsy/sellsy')->_getSellsyShippingTax();
    }
    
    
    public function testordersAction()
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId(100013418);
        
        $response = Mage::getModel('sellsy/sellsy')->_createOrder($order);
        
        Zend_debug::dump($response); die;
    }
    
    public function testinvoiceAction()
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId(100013434);
        $response = Mage::getModel('sellsy/sellsy')->_createOrder($order, true);
        Zend_debug::dump($response); die;
        
    }
    
    private function _createProduct($product)
    {
        //$product_tax_id = 
        $request = array(
            'method' => 'Catalogue.create',
            'params' => array(
                'type' => 'item',
                'item' => array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'tradename' => $product->getName(),
                    'unit' => 'package',
                    'unitAmount' => $product->getPrice(),
                    'purchaseAmount' => $product->getFinalPrice(),
                    'taxid' => $this->_getProductSellsyTaxId($product)
                    
                )
            )
        );
        
        //echo $this->_getProductSellsyTaxId($product);die;
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        return $response;
    }
    
    protected function _getProductSellsyTaxId($product){
        
        $product_tax_id = $product->getTaxClassId();
        
        //Zend_debug::dump($product->getData());die;
        
        $sellsy_tax_id = Mage::getModel('sellsy/taxes')->load($product_tax_id, 'magento_id');
        //Zend_debug::dump($sellsy_tax_id);die;
        
        return $sellsy_tax_id->getSellsyId();
    }
    
    private function _createSku($sku, $item_id){
        $request = array(
            'method' => 'Catalogue.createBarCode', 
            'params' => array (
                'linkedid'	=> $item_id,
                'barcode'	=> array(
                    'label'		=> 'SKU',
                    'value'		=> $sku
                )
            )
        );
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        return $response;       
    }
    
    private function _createAttribute($label, $code){
        $request =  array( 
            'method' => 'Catalogue.createVariationField', 
            'params' => array (
                'name'		=> $label,
                'syscode'	=> $code,
                'fields'	=> array(
                    0 => array(
                        'name'    => $label,
                        'syscode' => $code
                    )
                )
            )
        );
       $response = Mage::getModel('sellsy/connection')->requestApi($request);
       
       return $response;
    }
    
    protected function _checkIfProductExists($magento_product_id){
        $internal_product = Mage::getModel('sellsy/products')->load($magento_product_id, 'magento_id');
      
                $request =  array( 
                    'method' => 'Catalogue.getOne', 
                    'params' => array ( 
                        'type'				=> 'item',
                        'id'				=> $internal_product->getSellsyId(),
                        
                    )
        );
    }
    
    protected function _productExported($product_id){
        
        $product = Mage::getModel('sellsy/products')->load($product_id, 'magento_id');
        
        return ($product->getId()) ? true : false;
        
    }
    
    protected function _createClient($customer_id){
        
        $customer_data =  Mage::getModel('customer/customer')->load($customer_id);
        $customer_address_id = $customer_data->getDefaultBilling();
        $address = MAge::getModel('customer/address')->load($customer_address_id);
        //Zend_debug::dump($address);die;
        
        $request = array ( 
                    'method' => 'Client.create', 
                    'params' => array(
                        'third' => array(
                            'name'				=> $address->getFirstname().' '.$customer_data->getLastName(),
                            'ident'				=> $customer_id,
                            'type'				=> 'person',
                            'email'				=> $customer_data->getEmail(),
                            'tel'				=> $address->getTelephone(),
                            'fax'				=> $address->getFax(),

                        ),
                        'contact' => array(
                            'name'			=> $address->getLastname(),
                            'forename'		=> $address->getFirstname(),
                            'email'			=> $customer_data->getEmail(),
                            'tel'			=> $address->getTelephone(),
                        ),
                        'address' => array(
                            'name'		=> 'contact',
                            'part1'     => $address->getStreet()[0],
                            'part2'     => $address->getRegion(),
                            'zip'       => $address->getPostcode(),
                            'city'      => $address->getCity(),
                            'country'   => $address->getCountryId()
                            

                        )
                    )
                );
        
       // Zend_debug::dump($request);die;
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        $this->_mapCustomer($customer_id, $response->response->client_id);
       
       return $response->response->client_id;
    }
    
    
    protected function _mapCustomer($magento_id, $sellsy_id)
    {
        $customer = Mage::getModel('sellsy/customers');
        $customer->setSellsyId($sellsy_id);
        $customer->setMagentoId($magento_id);
        $customer->setUpdatedAt(now());
        $customer->save();
    }
    
    protected function _customerExported($customer_id)
    {
        $customer = Mage::getModel('sellsy/customers')->load($customer_id, 'magento_id');
        
        return ($customer->getId()) ? true : false;
    }
    
    protected function _createTax($tax_data){
        
        $request = array(
            'method' => 'Accountdatas.createTaxe',
            'params' => array(
                'taxe' => array(
                    'name'		=> $tax_data['class_name'],
                    'value'		=> $tax_data['value'],
                    'isEnabled'	=> 'Y'
                )
            )
        );
        
        //Zend_debug::dump($request);die;
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        return $response;
        
    }
    
    
    protected function _mapTax($magento_id, $sellsy_id)
    {
        $tax = Mage::getModel('sellsy/taxes');
        $tax->setSellsyId($sellsy_id);
        $tax->setMagentoId($magento_id);
        $tax->setUpdatedAt(now());
        $tax->save();
    }
    
    protected function _taxExported($tax_id)
    {
        $tax = Mage::getModel('sellsy/customers')->load($tax_id, 'magento_id');
        
        return ($tax->getId()) ? true : false;
    }
    
  public function _createOrder($order)
    {
        $magento_customer_id    = $order->getCustomerId();
        
        $sellsy_customer_id     = $this->_getSellsyClientId($magento_customer_id);
        
        if(!$sellsy_customer_id){
            $sellsy_customer_id = $this->_createClient($magento_customer_id);
        }
        
        //Zend_debug::dump($sellsy_customer_id);die;
        $sellsy_customer        = $this->_getSellsyClientById($sellsy_customer_id);
        $products               = $order->getAllVisibleItems();
        $rows                   = array();
        $products_iterator      = 1;
        $shipping_method        = $order->getShippingDescription();
        
        //Zend_debug::dump($shipping_method);die;
        //Zend_debug::dump($sellsy_customer);die;
        
        //Billing&Shipping Address
        	foreach ($sellsy_customer->response->address as $adIndex => $adDatas){
				
				if($sellsy_customer->response->client->name == $order->getBillingAddress()->getName()){
						$id_sellsy_thirdaddress = $adDatas->id;
                        $id_sellsy_shipaddress  = $adDatas->id;
				}
								
			}
            
            foreach($products as $product){
                //Zend_debug::dump($product->getId());die;
                $product = Mage::getModel('catalog/product')->load($product->getProductId());
                if($this->_productExported($product->getId())){
                    $product_sellsy_id = Mage::getModel('sellsy/products')->load($product->getId(), 'magento_id');
                    
                }
                else{
                    $export_product = $this->_createProduct($product);
                    //Zend_debug::dump($export_product);die;
                
                    $magento_product = Mage::getModel('sellsy/products');
                    $magento_product->setMagentoId($product->getId());
                    $magento_product->setSellsyId($export_product->response->item_id);
                    $magento_product->setUpdatedAt(now());
                    $magento_product->save();
                    
                    $product_sellsy_id = Mage::getModel('sellsy/products')->load($product->getId(), 'magento_id');
                }
                //Zend_debug::dump($product_sellsy_id);die;
                $sellsy_tax_id = Mage::getModel('sellsy/taxes')->load($product->getTaxClassId(), 'magento_id');
                //Zend_debug::dump($product->getId());die;
                //Zend_debug::dump(Mage::helper('tax')->getAllRatesByProductClass() );die;
                
                $rows[$products_iterator] = array(
                        'row_name' =>  $product->getName(),
                        'row_notes' => strip_tags($product->getShortDescription()),
                        'row_purchaseAmount' => $product->getFinalPrice(),
                        'row_type' => 'item',
                        'row_isOption'		=> 'N',
                        'row_linkedid'  => $product_sellsy_id->getSellsyId(),
                        'row_taxid' => $sellsy_tax_id->getSellsyId(),
                        'row_unitAmount' => $product->getPrice()
                );
                
                $products_iterator++;
            }
            
            /* Shipping */

            $products_iterator++;
            $this->_getShippingCarrier($shipping_method);
            $rows[$products_iterator] = array(
              					'row_type'			=> 'shipping',
								'row_shipping'		=> $shipping_method,
								'row_name'			=> $shipping_method,
								'row_unitAmount'	=> $order->getShippingAmount(),
								'row_taxid'			=> $sellsy_tax_id->getSellsyId(),
								'row_qt'			=> 1,
								'row_isOption'		=> "N"  
            );
        
            $request = array( 
							'method' => 'Document.create', 
							'params' => array (
								'document'		=> array(
									'doctype'				=> "order",
									'thirdid'				=> $sellsy_customer_id,
									'displayedDate'			=> $this->frToTimestamp(now()),
									'subject'				=> "Order #" . $order->getIncrementId(),
									'notes'					=> "",
									'tags'					=>  $order->getIncrementId(),
									'displayShipAddress'	=> "Y"
								),
								'thirdaddress'	=> array('id' => $id_sellsy_thirdaddress),
								'shipaddress'	=> array('id' => $id_sellsy_shipaddress),
								'row'			=> $rows
							)
						);
            
            
         //Zend_debug::dump($request);die;               
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
         if($response->status == self::RESPONSE_STATUS_SUCCESS){
            $this->_createOrderDelivery($order->getIncrementId(), $response->response->doc_id, $rows);
            $this->_mapOrder($order->getIncrementId(), $response->response->doc_id);
            $order->addStatusHistoryComment($this->__('Order exported to Sellsy'). ' Sellsy Order ID: '.$response->response->doc_id);
            $order->save();
         }
        
        return $response;
    }
    
    public static function frToTimestamp($date) {
		list($day, $month, $year) = explode('/', $date);
		$timestamp = mktime(0, 0, 0, $month, $day, $year);
		return $timestamp;
	}
    
    protected function _mapOrder($magento_id, $sellsy_id)
    {
        $order = Mage::getModel('sellsy/orders');
        $order->setSellsyId($sellsy_id);
        $order->setMagentoId($magento_id);
        $order->setUpdatedAt(now());
        $order->save();        
    }
    
    protected function _orderExported($order_id)
    {
        $order = Mage::getModel('sellsy/orders')->load($order_id, 'magento_id');
        
        return ($order->getId()) ? true : false;
        
    }
    
    protected function _getSellsyClientId($client_id)
    {
        $client = Mage::getModel('sellsy/customers')->load($client_id, 'magento_id');
        
        return $client->getSellsyId();
    }
    
    protected function _getSellsyClientById($sellsy_client_id)
    {
        $request = array(
            'method' => 'Client.getOne',
            'params' => array( 
                'clientid'	=> $sellsy_client_id
            )
        );
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        if($response->status == self::RESPONSE_STATUS_SUCCESS)
            return $response;
        else
            return false;
    }
    
    protected function _createShippingCarrier($carrier_name){
        
        $request = array(
						'method' =>  'Accountdatas.recordShipping',
						'params' => array(
						'shipping'	=> array(
						'asNew'		=> "Y",
						'isEnabled'	=> "Y",
						'name'		=> $carrier_name,
					)
				)
		);
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        if($response->status == self::RESPONSE_STATUS_SUCCESS)
            return $response;
        else
            return false;
    }
    
    protected function _getShippingCarrier($carrier_name)
    {
        $create_carrier = true;
        $requestTrans = array(
					'method' => 'Accountdatas.getShippingList',
					'params' => array()
					);
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        if($response->status == self::RESPONSE_STATUS_SUCCESS){
            foreach($response->response as $oneShipping){
					if(isset($oneShipping->status) && $oneShipping->status != 'deleted'){
										
							if($oneShipping->name == $carrier_name){
											$creerShipping = false;
										}
										
									}
								}
        }
        
        if($create_carrier){
            $this->_createShippingCarrier($carrier_name);
        }
    }
    
    protected function _createOrderDelivery($magento_order_id, $sellsy_order_id, $rows)
    {
        $request =  array( 
							'method' => 'Document.create', 
							'params' => array (
								'document'	=> array(
									'doctype'				=> "delivery",
									'thirdid'				=> $sellsy_order_id,
									'displayedDate'			=> $this->frToTimestamp(now()),
									'subject'				=> "Order #" . $magento_order_id,
									'notes'					=> "",
									'tags'					=>  $magento_order_id,
									'displayShipAddress'	=> "Y"
								),
								'row'		=> $rows
							)
						);
        
        zend_debug::dump($request);die;
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        if($response->status == self::RESPONSE_STATUS_SUCCESS)
            return $response;
        else
            return false;
        
    }
    
}