<?php
namespace Mv\Megaventory\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $table = $installer->getTable('megaventory_inventories');
        
        if (version_compare($context->getVersion(), '1.3.0', '<=') && version_compare($context->getVersion(), '1.2.0', '>=')) {
            if ($installer->getConnection()->tableColumnExists($table, 'stock_source_code') === false) {
                $installer->getConnection()->addColumn(
                    $table,
                    'stock_source_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 200,
                        'comment' => 'Magento Inventory Source'
                    ]
                );
            }

            if ($installer->getConnection()->tableColumnExists($table, 'mv_adjustment_plus_type_id') === false) {
                $installer->getConnection()->addColumn(
                    $table,
                    'mv_adjustment_plus_type_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Megaventory Adjustment Plus Template ID'
                    ]
                );
            }

            if ($installer->getConnection()->tableColumnExists($table, 'mv_adjustment_minus_type_id') === false) {
                $installer->getConnection()->addColumn(
                    $table,
                    'mv_adjustment_minus_type_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Megaventory Adjustment Minus Template'
                    ]
                );
            }

            if ($installer->getConnection()->tableColumnExists($table, 'isdefault') !== false) {
                $installer->getConnection()->dropColumn(
                    $table,
                    'isdefault'
                );
            }

            $table = $installer->getConnection()
            ->newTable($installer->getTable('megaventory_order_templates'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'shortname',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                ['nullable' => true, 'default' => null],
                'Short Name'
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['nullable' => true, 'default' => null],
                'Name'
            )
            ->addColumn(
                'megaventory_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Megaventory ID'
            )
            ->addColumn(
                'magento_website_ids',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                500,
                [],
                'Magento Websites'
            );
            
            $installer->getConnection()->createTable($table);

            $table = $installer->getConnection()
            ->newTable($installer->getTable('megaventory_adjustment_templates'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'shortname',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                ['nullable' => true, 'default' => null],
                'Short Name'
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['nullable' => true, 'default' => null],
                'Name'
            )
            ->addColumn(
                'stock_change',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => 0],
                'Stock Change(Plus/Minus)'
            )
            ->addColumn(
                'megaventory_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Megaventory ID'
            );
            
            $installer->getConnection()->createTable($table);
        }
        elseif(version_compare($context->getVersion(), '1.4.2', '<')){
            if ($installer->getConnection()->tableColumnExists($installer->getTable('megaventory_inventories'), 'adjustment_doc_status') === false) {
                $installer->getConnection()->addColumn(
                    $installer->getTable('megaventory_inventories'),
                    'adjustment_doc_status',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => 'Pending',
                        'length' => 100,
                        'comment' => 'Issue Adjustment Document with this Status'
                    ]
                );
            }
        }

        $installer->endSetup();
    }
}
