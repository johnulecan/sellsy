<?php class MageLine_Sellsy_Model_Sellsy extends Mage_Core_Model_Abstract
{
    
    const RESPONSE_STATUS_SUCCESS = 'success';
    const DEFAULT_TAX_RATE = 2236931;
    
    public function _createProduct($product)
    {
        //$product_tax_id =
        $price = $product->getFinalPrice();
        if($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
            $price = $this->getBundlePrice($product);
        }
        $request = array(
            'method' => 'Catalogue.create',
            'params' => array(
                'type' => 'item',
                'item' => array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'tradename' => $product->getName(),
                    'unit' => 'package',
                    'unitAmount' => $price,
                    'purchaseAmount' => $price,
                    'taxid' => ($this->_getProductSellsyTaxId($product) > 0) ? $this->_getProductSellsyTaxId($product) : self::DEFAULT_TAX_RATE,
                    
                )
            )
        );
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        if($response->status == self::RESPONSE_STATUS_SUCCESS)
            return $response;
        else{
            Zend_debug::dump($response);
            die;
        }
    }
    
            public function getBundlePrice($product) {
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $optionCol= $product->getTypeInstance(true)
                                    ->getOptionsCollection($product);
                $selectionCol= $product->getTypeInstance(true)
                                       ->getSelectionsCollection(
                    $product->getTypeInstance(true)->getOptionsIds($product),
                    $product
                );
                $optionCol->appendSelections($selectionCol);
                $price = $product->getPrice();
        
                foreach ($optionCol as $option) {
                    if($option->required) {
                        $selections = $option->getSelections();
                        $minPrice = min(array_map(function ($s) {
                                        return $s->price;
                                    }, $selections));
                        if($product->getSpecialPrice() > 0) {
                            $minPrice *= $product->getSpecialPrice()/100;
                        }
        
                        $price += round($minPrice,2);
                    }  
                }
                return $price;
            } else {
                return false;
            }
        }
    
    public function _getProductSellsyTaxId($product){
        
        $product_tax_id = $product->getTaxClassId();
        
        $sellsy_tax_id = Mage::getModel('sellsy/taxes')->load($product_tax_id, 'magento_id');
        //Zend_debug::dump($sellsy_tax_id);die;
        
        return $sellsy_tax_id->getSellsyId();
    }
    
    public function _createSku($sku, $item_id){
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
    
    public function _createAttribute($label, $code){
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
    
    public function _checkIfProductExists($magento_product_id){
        $internal_product = Mage::getModel('sellsy/products')->load($magento_product_id, 'magento_id');
      
                $request =  array( 
                    'method' => 'Catalogue.getOne', 
                    'params' => array ( 
                        'type'				=> 'item',
                        'id'				=> $internal_product->getSellsyId(),
                        
                    )
        );
    }
    
    public function _productExported($product_id){
        
        $product = Mage::getModel('sellsy/products')->load($product_id, 'magento_id');
        
        return ($product->getId()) ? true : false;
        
    }
    
    public function _createClient($customer_id){
        
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
    
    
    public function _mapCustomer($magento_id, $sellsy_id)
    {
        $customer = Mage::getModel('sellsy/customers');
        $customer->setSellsyId($sellsy_id);
        $customer->setMagentoId($magento_id);
        $customer->setUpdatedAt(now());
        $customer->save();
    }
    
    public function _customerExported($customer_id)
    {
        $customer = Mage::getModel('sellsy/customers')->load($customer_id, 'magento_id');
        
        return ($customer->getId()) ? true : false;
    }
    
    public function _createTax($tax_data){
        
        $request = array(
            'method' => 'Accountdatas.createTaxe',
            'params' => array(
                'taxe' => array(
                    'name'		=> $tax_data['code'],
                    'value'		=> $tax_data['rate'],
                    'isEnabled'	=> 'Y'
                )
            )
        );
        
        //Zend_debug::dump($request);die;
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        return $response;
        
    }
    
    
    public function _mapTax($magento_id, $sellsy_id)
    {
        $tax = Mage::getModel('sellsy/taxes');
        $tax->setSellsyId($sellsy_id);
        $tax->setMagentoId($magento_id);
        $tax->setUpdatedAt(now());
        $tax->save();
    }
    
    public function _taxExported($tax_id)
    {
        $tax = Mage::getModel('sellsy/customers')->load($tax_id, 'magento_id');
        
        return ($tax->getId()) ? true : false;
    }
    
      public function _createOrder($order, $invoice = false, $magento_invoice_id = false)
    {
        
        $magento_customer_id    = $order->getCustomerId();
        
        $sellsy_customer_id     = $this->_getSellsyClientId($magento_customer_id);
        
        if(!$sellsy_customer_id){
            $sellsy_customer_id = $this->_createClient($magento_customer_id);
        }
        
        //Zend_debug::dump($sellsy_customer_id);die;
        $sellsy_customer        = $this->_getSellsyClientById($sellsy_customer_id);
        $products               = $order->getItemsCollection();
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
            
            
            //Zend_debug::dump(count($products));die;
            foreach($products as $item){
                $isBundle = false;
                //Zend_debug::dump($item->getName());die;
                $product = Mage::getModel('catalog/product')->load($item->getProductId());
                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
                    $isBundle = true;
                }
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
                //Zend_debug::dump($product_sellsy_id->getData());die;
                $sellsy_tax_id = Mage::getModel('sellsy/taxes')->load($product->getTaxClassId(), 'magento_id');
                //Zend_debug::dump($sellsy_tax_id->getData());die;
                //Zend_debug::dump(Mage::helper('tax')->getAllRatesByProductClass() );die;
                
                $rows[$products_iterator] = array(
                        'row_name' =>  $item->getName(),
                        'row_notes' => strip_tags($product->getShortDescription()),
                        'row_purchaseAmount' => $item->getPrice(),
                        'row_type' => 'item',
                        'row_isOption'		=> $item->getParentItemId() ? 'Y' : 'N',
                        'row_linkedid'  => $product_sellsy_id->getSellsyId(),
                        'row_taxid' => ($sellsy_tax_id->getSellsyId()) ? $sellsy_tax_id->getSellsyId() : self::DEFAULT_TAX_RATE,
                        'row_unitAmount' => $item->getPrice(),
                        'row_qt'			=> $item->getQtyOrdered(),
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
								'row_taxid'			=> ($sellsy_tax_id) ? $sellsy_tax_id->getSellsyId() : self::DEFAULT_TAX_RATE,
								'row_qt'			=> 1,
								'row_isOption'		=> "N"  
            );
            
            if(!$invoice){
        
                    $request = array( 
                                    'method' => 'Document.create', 
                                    'params' => array (
                                        'document'		=> array(
                                            'doctype'				=> "order",
                                            'thirdid'				=> $sellsy_customer_id,
                                            'displayedDate'			=> time(),
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
                    $this->_updateDocumentStep($response->response->doc_id, 'order', 'invoiced');
                    $this->_mapOrder($order->getIncrementId(), $response->response->doc_id);
                    $order->addStatusHistoryComment('Order exported to Sellsy. Sellsy Order ID: '.$response->response->doc_id);
                    $order->save();
                    return $response;
                 }else{
                    Zend_debug::dump($response->error);
                    Zend_debug::dump($rows);
                    die;
                 }
                
                
            }else{
                
                
                $sellsy_order = Mage::getModel('sellsy/orders')->load($order->getIncrementId(), 'magento_id');
                //Zend_debug::dump($sellsy_order->getData());die;
                return $this->createInvoice($order->getIncrementId(), $sellsy_order->getSellsyId(), $rows, $magento_invoice_id);
            }
    }
    
    public static function frToTimestamp($date) {
        $date = date("d/m/Y", strtotime($date));
		list($day, $month, $year) = explode('/', $date);
		$timestamp = mktime(0, 0, 0, $month, $day, $year);
		return $timestamp;
	}
    
    public function _mapOrder($magento_id, $sellsy_id)
    {
        $order = Mage::getModel('sellsy/orders');
        $order->setSellsyId($sellsy_id);
        $order->setMagentoId($magento_id);
        $order->setUpdatedAt(now());
        $order->save();        
    }
    
    public function _orderExported($order_id)
    {
        $order = Mage::getModel('sellsy/orders')->load($order_id, 'magento_id');
        
        return ($order->getId()) ? true : false;
        
    }
    
    public function _getSellsyClientId($client_id)
    {
        $client = Mage::getModel('sellsy/customers')->load($client_id, 'magento_id');
        
        return $client->getSellsyId();
    }
    
    public function _getSellsyClientById($sellsy_client_id)
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
    
    public function _createShippingCarrier($carrier_name){
        
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
    
    public function _getShippingCarrier($carrier_name)
    {
        $create_carrier = true;
        $requestTrans = array(
					'method' => 'Accountdatas.getShippingList',
					'params' => array()
					);
        
        $response = Mage::getModel('sellsy/connection')->requestApi($requestTrans);
        
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
    
    public function _createOrderDelivery($magento_order_id, $sellsy_order_id, $rows)
    {
        $request =  array( 
							'method' => 'Document.create', 
							'params' => array (
								'document'	=> array(
									'doctype'				=> "delivery",
									'thirdid'				=> $sellsy_order_id,
									'displayedDate'			=> time(),
									'subject'				=> "Order #" . $magento_order_id,
									'notes'					=> "",
									'tags'					=>  $magento_order_id,
									'displayShipAddress'	=> "Y"
								),
								'row'		=> $rows
							)
						);
        
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        if($response->status == self::RESPONSE_STATUS_SUCCESS)
            return $response;
        else
            return false;
        
    }
    
    public function _getSellsyShippingTax()
    {
        $magento_tax_id = 0;
        $taxes_collection = Mage::getModel('tax/class')->getCollection()->addFieldToFilter('class_type', 'PRODUCT')->load()->getData();
        //Zend_debug::dump($taxes_collection);die;
        foreach($taxes_collection as $tax_data){
            
               if($tax_data['class_name'] == 'Livraison'){
                    $magento_tax_id = $tax_data['class_id'];
               }

               //Zend_debug::dump($tax_data);die;
        }
        
        $sellsy_tax_id = Mage::getModel('sellsy/taxes')->load($magento_tax_id, 'magento_id');
       //Zend_debug::dump($sellsy_tax_id);die;
        return $sellsy_tax_id;
    }
    
    private function _isOrderInvoiced($order)
    {
        $isInvoiced = true;
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyToInvoice()>0 && !$item->getLockedDoInvoice()) {
                $isInvoiced = false;
                break;
            }
        }
        
        return $isInvoiced;
    }
    
    private function _isFullCreditMemo($order){
        $isFullCretitmemo = false;
        if (abs($order->getStore()->roundPrice($order->getTotalPaid()) - $order->getTotalRefunded()) < .0001) {
            $isFullCretitmemo = true;
        }
        
        return $isFullCretitmemo;
    }
    
    public function createInvoice($magento_order_id, $sellsy_order_id, $rows, $magento_invoice_id){
        
        $order = Mage::getModel('sales/order')->loadByIncrementId($magento_order_id);
        
        $magento_customer_id    = $order->getCustomerId();
        
        $sellsy_customer_id     = $this->_getSellsyClientId($magento_customer_id);
        
        if(!$sellsy_customer_id){
            $sellsy_customer_id = $this->_createClient($magento_customer_id);
        }
        
        //Zend_debug::dump($sellsy_customer_id);die;
        $sellsy_customer        = $this->_getSellsyClientById($sellsy_customer_id);
        
         //Billing&Shipping Address
        	foreach ($sellsy_customer->response->address as $adIndex => $adDatas){
				
				if($sellsy_customer->response->client->name == $order->getBillingAddress()->getName()){
						$id_sellsy_thirdaddress = $adDatas->id;
                        $id_sellsy_shipaddress  = $adDatas->id;
				}
								
			}
        
        // def des adresses
						if ($id_sellsy_thirdaddress != -1){
							$request['params']['thirdaddress'] = array('id' => $id_sellsy_thirdaddress);
						}
							
						if ($id_sellsy_shipaddress != -1){
							$request['params']['shipaddress'] = array('id' => $id_sellsy_shipaddress);
						}
						
						if($id_sellsy_bl != -1){
							$request['params']['document']['parentId'] = $id_sellsy_bl;
						}
                        
        $request =  array( 
							'method' => 'Document.create', 
							'params' => array (
								'document'	=> array(
									'doctype'				=> "invoice",
									'thirdid'				=> $sellsy_customer_id,
									'displayedDate'			=> time(),
									'subject'				=> "Commande #" . $magento_order_id,
									'notes'					=> "",
									'tags'					=> $magento_order_id,
									'displayShipAddress'	=> "Y"
								),
								'row'		=> $rows
							)
						);
        
 
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        
        if($response->status == self::RESPONSE_STATUS_SUCCESS){
            $this->_mapInvoice($magento_invoice_id, $response->response->doc_id);
            $payment_method_id = $order->getPayment()->getMethod();
            //Zend_debug::dump($payment_method_id);die;
            $sellsy_payment = Mage::getModel('sellsy/payments')->load($payment_method_id, 'magento_id');
            $sellsy_payment_id = $sellsy_payment->getSellsyId();
            
            if($sellsy_payment_id > 0){             
                $payment_id = $sellsy_payment_id;
            }else{
                 $payment_id = $this->_createSellsyPaymentMethod($order->getPayment()->getMethodInstance()->getTitle(), $payment_method_id);               
                
            }
            
            if($payment_id > 0){
                //Zend_Debug::dump($order->getGrandTotal());die;
                $res = $this->_createSellsyInvoicePayment($order->getGrandTotal(), $payment_id, $response->response->doc_id);
            }
            
            return $response->success;
        }
        else
            return $response->error;
    }
    
    private function _addOrderPayment(){
        
    }
    
    public function _mapInvoice($magento_id, $sellsy_id)
    {
        if($magento_id){
            $invoice = Mage::getModel('sellsy/invoices');
            //Zend_debug::dump($sellsy_id);die;
           // Zend_debug::dump($invoice->getData());die;
            $invoice->setSellsyId($sellsy_id);
            $invoice->setMagentoId($magento_id);
            $invoice->setUpdatedAt(now());
            $invoice->save();
            
        }
    }
    
    public function _mapPayment($magento_id, $sellsy_id)
    {
        if($magento_id){
            $payment = Mage::getModel('sellsy/payments');
            //Zend_debug::dump($sellsy_id);die;
           // Zend_debug::dump($invoice->getData());die;
            $payment->setSellsyId($sellsy_id);
            $payment->setMagentoId($magento_id);
            $payment->setUpdatedAt(now());
            $payment->save();
            
        }
    }
    
    public function _createSellsyPaymentMethod($method, $magento_id){
        $request = array(
								'method' =>  'Accountdatas.createPayMedium',
							    'params' => array(
									'paymedium'	=> array(
										'isEnabled'	=> "Y",
										'value'	=> $method
									)
								)
							);
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
        if($response->status == self::RESPONSE_STATUS_SUCCESS){
            $this->_mapPayment($magento_id, $response->response->mediumid);        
            return $response->response->mediumid;
        }
        else{
            return false;
        }
    }
    
    public function _createSellsyInvoicePayment($total_paid, $sellsy_method_id, $sellsy_invoice_id){
        	/**
						 * Payment creation
						 */
						$request = array( 
							'method' => 'Document.createPayment', 
							'params' => array (
								'payment' => array(
									'date'		=> time(),
									'amount'	=> $total_paid,
									'medium'	=> $sellsy_method_id,
									'email'		=> "N",
									'doctype'	=> "invoice",
									'docid'		=> $sellsy_invoice_id
								)
							)
						);
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
                        
         if($response->status == self::RESPONSE_STATUS_SUCCESS){
            return true;
        }
        else{
            Zend_debug::dump($response->error);die;
        }
    
    }
    
    public function _createCreditMemo($order, $credit_memo)
    {
        //Zend_debug::dump($order->getIncrementId());die;
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
            
            
            
            foreach($products as $item){
                //Zend_debug::dump($product->getId());die;
                $product = Mage::getModel('catalog/product')->load($item->getProductId());
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
                        'row_purchaseAmount' => $item->getPrice(),
                        'row_type' => 'item',
                        'row_isOption'		=> 'N',
                        'row_linkedid'  => $product_sellsy_id->getSellsyId(),
                        'row_taxid' => $sellsy_tax_id->getSellsyId(),
                        'row_unitAmount' => $item->getPrice()
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
								'row_taxid'			=> ($sellsy_tax_id) ? $sellsy_tax_id->getSellsyId() : 2236931,
								'row_qt'			=> 1,
								'row_isOption'		=> "N"  
            );
            
            //$invoice = Mage::getModel('sellsy/invoices')->load();
            
            $request = array( 
                                    'method' => 'Document.create', 
                                    'params' => array (
                                        'document'		=> array(
                                            'doctype'				=> "creditnote",
                                            'thirdid'				=> $sellsy_customer_id,
                                            'displayedDate'			=> time(),
                                            'subject'				=> "Refund for Order #" . $order->getIncrementId(),
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
                    $this->_linkCreditNote($response->response->doc_id, $this->_getOrderInvoices($order), $order->getTotalPaid());
                    //$this->_mapCreditMemo($credit_memo->getIncrementId(), $response->response->doc_id);
                   
                 }
                
                return $response;
    }
    
    public function _mapCreditMemo($magento_id, $sellsy_id)
    {
        if($magento_id > 0 && $sellsy_id > 0){
            $memo = Mage::getModel('sellsy/creditmemos');
            $memo->setSellsyId($sellsy_id);
            $memo->setMagentoId($magento_id);
            $memo->setUpdatedAt(now());
            $memo->save();
            
        }   
    }
    
    public function _linkCreditNote($credit_note_id, $invoice_ids, $amount)
    {
        foreach($invoice_ids as $key => $invoice_id){
            $request =  array( 
                    'method' => 'Document.linkToDoc', 
                    'params' => array(
                        'relatedid' 	=> $invoice_id,
                        'relatedtype' 	=> 'invoice',
                        'relateds' 	=> array(
                            'docid'		=> $credit_note_id,
                            'doctype'	=> 'creditnote',
                            'amount'	=> $amount,
                        ),
                    )
                );
            $response = Mage::getModel('sellsy/connection')->requestApi($request);
        }
    }
    
    private function _getOrderInvoices($order)
    {
        $model = Mage::getSingleton('sellsy/invoices');
        if($order && $order->hasInvoices()){
            $invIncrementIDs = array();
            foreach ($order->getInvoiceCollection() as $inv) {
               $sellsy_id = $model->load($inv->getId(), 'magento_id')->getSellsyId();
                $invIncrementIDs[] = $sellsy_id;
            }
        }
        
        return $invIncrementIDs;
        
        
    }
    
    private function _updateDocumentStep($sellsy_id, $doc_type, $step)
    {
        $request =  array( 
            'method' => 'Document.updateStep', 
            'params' => array (
                'docid'	=> $sellsy_id,
                'document' => array(
                    'doctype'	=> $doc_type,
                    'step'		=> $step
                )
            ),
        );
        $response = Mage::getModel('sellsy/connection')->requestApi($request);
    }
}