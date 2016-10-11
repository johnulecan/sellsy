<?php 
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$tables = array();

$tables['products'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/products'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'Unique identifier'
    )
    ->addColumn(
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento Product Entity ID'
    )
    ->addColumn(
        'sellsy_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Sellsy Product Internal ID'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Last updated at'
    );
    
$tables['customers'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/customers'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'Unique identifier'
    )
    ->addColumn(
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento Customer ID'
    )
    ->addColumn(
        'sellsy_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Sellsy Customer Internal ID'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Last updated at'
    );
    
$tables['attributes'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/attributes'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'Unique identifier'
    )
    ->addColumn(
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento Attribute Entity ID'
    )
    ->addColumn(
        'sellsy_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Sellsy Attribute Internal ID'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Last updated at'
    );
    
$tables['product_attributes'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/product_attributes'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'Unique identifier'
    )
    ->addColumn(
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento Product Entity ID'
    )
    ->addColumn(
        'sellsy_ids', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(), 'Sellsy Product Variations Internal IDs'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Last updated at'
    );
    
$tables['taxes'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/taxes'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'Unique identifier'
    )
    ->addColumn(
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento Tax Entity ID'
    )
    ->addColumn(
        'sellsy_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Sellsy Tax Internal ID'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Last updated at'
    );
    
$tables['orders'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/orders'))
    ->addColumn(
        'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'Unique identifier'
    )
    ->addColumn(
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento Order Real ID'
    )
    ->addColumn(
        'sellsy_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Sellsy Order Internal IDs'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Last updated at'
    );
    
foreach($tables as $table_name => $table){
    
    if (!$installer->getConnection()->isTableExists($table->getName())) {
        try{
            $installer->getConnection()->createTable($table);
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }

}

$installer->endSetup();