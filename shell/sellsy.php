<?php
/**
* MageLine
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@magentocommerce.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Please do not edit or add to this file if you wish to upgrade
* Magento or this extension to newer versions in the future.
** MageLine *give their best to conform to
* "non-obtrusive, best Magento practices" style of coding.
* However,* MageLine *guarantee functional accuracy of
* specific extension behavior. Additionally we take no responsibility
* for any possible issue(s) resulting from extension usage.
* We reserve the full right not to provide any kind of support for our free extensions.
* Thank you for your understanding.
*
* @category MageLine
* @package Sellsy
* @author Ioan Ulecan <ioan.ulecan@mageline.com>
* @copyright Copyright (c) MageLine (http://www.mageline.com/)
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/

require_once 'abstract.php';
class MageLine_Shell_Sellsy extends Mage_Shell_Abstract
{
    protected $_argname = '';
    const EXPORT_ORDERS_FROM_DATE = '2016-01-01 00:00:00';
    
    public function __construct()
    {
        parent::__construct();
        
        set_time_limit(0);
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        if($this->getArg('export')) {
            $this->_argname = $this->getArg('export');
        }
        
        $this->_model = Mage::getModel('sellsy/sellsy');
    }
    
    public function run()
    {
        if(empty($this->_argname))
            echo $this->usageHelp();
            
        try{
            if($this->_argname == 'orders'){
                $orders = $this->_prepareOrdersForExport();
                $iterator = 1;
                $nr_orders = $orders->getSize();
                foreach($orders as $order){
                    $progress = ($iterator * 100)/$nr_orders;
                    $progress = number_format($progress, 2);
                    echo PHP_EOL.'[x] Exporting order: '.$order->getIncrementId().' ( '.$progress.'% )';
                    $response = $this->_model->_createOrder($order);
                    $iterator++;
                }
                echo PHP_EOL.'    -------   done   ------'.PHP_EOL;
            }
            elseif ($this->_argname == 'invoices')
            {
                $orders = $this->_prepareOrdersForExport();
                $iterator = 1;
                $nr_orders = $orders->getSize();
                foreach($orders as $order){
                    $progress = ($iterator * 100)/$nr_orders;
                    $progress = number_format($progress, 2);
                    $invoice_id = $this->getInvoiceId($order);
                    if($invoice_id){
                        echo PHP_EOL.'[x] Exporting invoice: '.$order->getIncrementId().' ( '.$progress.'% )';
                        $response = $this->_model->_createOrder($order, true, $invoice_id);
                        $iterator++;
                    }
                    
                }
                echo PHP_EOL.'    -------   done   ------'.PHP_EOL;
            }
        }catch(Exception $e){
            echo $e->getMessage();
        }
        
    }
    
    private function _getInvoiceId($order){
        
    }
    
    private function _prepareOrdersForExport()
    {
        $fromDate = date('Y-m-d H:i:s', strtotime(self::EXPORT_ORDERS_FROM_DATE));
        $toDate = date('Y-m-d H:i:s');
        /* Get the collection */
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
            ->addAttributeToFilter('status', array('in' => array(Mage_Sales_Model_Order::STATE_COMPLETE, Mage_Sales_Model_Order::STATE_PROCESSING)));
        
        if($orders->getSize() > 0){
            printf(
                'Number of orders to export: %s'."\n",
                $orders->getSize()
            );
            return $orders;
        }else{
            return false;
        }
        
        
    }
    
    
    public function usageHelp()
    {
         return <<<USAGE
            Usage:  php -f sellsy.php -- [options]
             
              --export <argvalue>       orders/clients/products/taxes
             
              help                   This help
 
USAGE;
    }
}

    // Instantiate
    $shell = new MageLine_Shell_Sellsy();
     
    // Initiate script
    $shell->run();