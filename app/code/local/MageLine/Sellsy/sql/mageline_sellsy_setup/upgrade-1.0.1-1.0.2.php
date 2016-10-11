<?php 
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$tables = array();

    $tables['invoices'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/invoices'))
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
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento Invoice ID'
    )
    ->addColumn(
        'sellsy_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Sellsy Invoice Internal IDs'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Last updated at'
    );
    
    $tables['payments'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/payments'))
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
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento Payment ID'
    )
    ->addColumn(
        'sellsy_id', Varien_Db_Ddl_Table::TYPE_TEXT, 250, array(), 'Sellsy Payment Internal IDs'
    )
    ->addColumn(
        'updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Last updated at'
    );
    
    $tables['creditmemos'] = $installer->getConnection()
    ->newTable($installer->getTable('sellsy/creditmemos'))
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
        'magento_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Magento credit memo ID'
    )
    ->addColumn(
        'sellsy_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 9, array(), 'Sellsy credit memo Internal IDs'
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