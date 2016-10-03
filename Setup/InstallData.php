<?php
/**
 * Copyright © 2016 Megaventory. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mv\Megaventory\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
   
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
    	$configTable = $setup->getTable('core_config_data');
    	
    	/* delete from `{$installer->getTable('core_config_data')}` where path = 'cataloginventory/item_options/manage_stock';
    	insert into `{$installer->getTable('core_config_data')}` (scope, scope_id, path, value) values ('default',0,'cataloginventory/item_options/manage_stock',1);
    	delete from `{$installer->getTable('core_config_data')}` where path = 'api/config/wsdl_cache_enabled';
    	insert into `{$installer->getTable('core_config_data')}` (scope, scope_id, path, value) values ('default',0,'api/config/wsdl_cache_enabled',1);
    	delete from `{$installer->getTable('core_config_data')}` where path = 'api/config/compliance_wsi';
    	insert into `{$installer->getTable('core_config_data')}` (scope, scope_id, path, value) values ('default',0,'api/config/compliance_wsi',0); */
    	
    	$setup->getConnection()->delete(
    			$configTable,
    			['path = ?' => 'cataloginventory/item_options/manage_stock']
    	);
    	
    	$setup->getConnection()->insert(
    			$configTable,
    			['scope' => 'default', 'scope_id' => '0', 'path' => 'cataloginventory/item_options/manage_stock', 'value' => '1']
    	);
    	
    	/* $setup->getConnection()->delete(
    			$configTable,
    			['path = ?' => 'api/config/wsdl_cache_enabled']
    	);

    	$setup->getConnection()->delete(
    			$configTable,
    			['path = ?' => 'api/config/compliance_wsi']
    	); */
    	
    }
}
