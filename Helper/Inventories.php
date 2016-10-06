<?php

namespace Mv\Megaventory\Helper;

use \Mv\Megaventory\Logger\Logger;
use \Mv\Megaventory\Model\LogFactory;

class Inventories extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_scopeConfig;
    private $_mvHelper;
    private $_productstocksLoader;
    private $_inventoriesLoader;
    private $_inventoriesCollection;
    private $_resource;
	protected $_stockItemFactory;
	private $_messageManager;
	private $_backendUrl;
	private $APIKEY;
    
	protected $logger;
	protected $mvLogFactory;
	
	
	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		Data $mvHelper,
        \Mv\Megaventory\Model\ProductstocksFactory $productStocksLoader,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
        \Mv\Megaventory\Model\ResourceModel\Inventories\Collection $inventoriesCollection,
		\Magento\Framework\App\ResourceConnection $recource,
		\Magento\CatalogInventory\Model\Stock\ItemFactory $stockItemFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
		LogFactory $mvLogFactory,
		Logger $logger
    ) {
		$this->_scopeConfig = $scopeConfig; 
        $this->_mvHelper = $mvHelper;
        $this->_productstocksLoader = $productStocksLoader;
        $this->_inventoriesLoader = $inventoriesLoader;
        $this->_inventoriesCollection = $inventoriesCollection;
        $this->_resource = $recource;
        $this->_stockItemFactory = $stockItemFactory;
        $this->_messageManager = $messageManager;
        $this->_backendUrl = $backendUrl;
    	$this->APIKEY = $this->_scopeConfig->getValue('megaventory/general/apikey');
                
		$this->mvLogFactory = $mvLogFactory;
		$this->logger = $logger;
        parent::__construct($context);
    }
    
    public function getInventories()
    {
    	return $this->_inventoriesCollection->load();
    }

    public function getInventoriesFromMegaventory($apikey = false, $apiurl = false, $enabled = -1)
    {
    	if ($apikey  != false)
    		$key = $apikey;
    	else
    		$key = $this->APIKEY;
    
    
    	$data = array
    	(
    			'APIKEY' => $key
    	);
    		
    	$json_result = $this->_mvHelper->makeJsonRequest($data ,'InventoryLocationGet',0, $apiurl, $enabled);
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode != '0')
    		return false;
    
    	try{
    		$mvInventoryLocations = $json_result['mvInventoryLocations'];
    	}
    	catch (\Exception $ex){
    		return false;
    	}
    
    	return count($mvInventoryLocations);
    }
    
	public function getInventoryFromMegaventoryId($mvInventoryId){		
		$inventory = $this->_inventoriesLoader->create()->load($mvInventoryId, 'megaventory_id');

		if ($inventory->getId() == false)
			return false;
		
		return $inventory;
	}

    public function syncrhonizeInventories($apikey = -1, $apiurl = -1)
    {
    	if ($apikey  != -1)
    		$key = $apikey;
    	else
    		$key = $this->APIKEY;
    		
    	$data = array
    	(
    			'APIKEY' => $key
    	);
    		    
    	try{
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'InventoryLocationGet',0, $apiurl);
    	}
    	catch (\Exception $ex){
    		return false;
    	}
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode != '0')
    		return false;
    
    	$mvInventoryLocations = $json_result['mvInventoryLocations'];
    
    	$i = 0;
    	$result = -1;
    	foreach($mvInventoryLocations as $mvInventory)
    	{
    		$inventory = $this->checkIfInventoryExists($mvInventory);
    		if ($inventory == false){
    			$this->insertInventory($mvInventory);
    		}
    		else
    			$this->updateInventory($inventory, $mvInventory);
    			
    		$mvIds[] = $mvInventory['InventoryLocationID'];
    		$i++;
    	}
    
    	if (count($mvIds) > 0)
    		$this->deleteNotExistentInventories($mvIds);
        
    
    	$this->updateAllStock();
    
    
    	if ($i>0)
    		return $i;
    
    	return $result;
    }
    
    public function updateAllStock()
    {
    	$data = array
    	(
    			'APIKEY' => $this->APIKEY
    	);
    	
    	$json_result = $this->_mvHelper->makeJsonRequest($data ,'InventoryLocationStockGet',0);
    		
    	if ($json_result != false){
    		$productStockList = $json_result['mvProductStockList'];
    		$inventoryStockData = array();
    		$configValue = $this->_scopeConfig->getValue('cataloginventory/options/can_subtract');
    		foreach ($productStockList as $productStockListItem){

    			$pId = $this->getIdByMegaventoryId($productStockListItem['productID']);
    			
    			if (empty($pId) || $pId == false)
    				continue;
    				
    			$warehouseStocks = $productStockListItem['mvStock'];
    			$totalStock = 0;
    			foreach ($warehouseStocks as $warehouseStock)
    			{
    				$inventoryStockData['stockqty'] = $warehouseStock['StockPhysical'];
    				$inventoryStockData['stockqtyonhold']= $warehouseStock['StockOnHold'];
    				$inventoryStockData['stocknonshippedqty'] = $warehouseStock['StockNonShipped'];
    				$inventoryStockData['stocknonallocatedwoqty'] = $warehouseStock['StockNonAllocatedWOs'];
    				$inventoryStockData['stocknonreceivedqty'] = $warehouseStock['StockNonReceivedPOs'];
    				$inventoryStockData['stockwipcomponentqty'] = 0;
    				$inventoryStockData['stocknonreceivedwoqty'] = $warehouseStock['StockNonReceivedWOs'];
    				$inventoryStockData['stockalarmqty'] = $warehouseStock['StockAlertLevel'];
    					
    				//warehouseID changed in megaventory API v2
    				//we need to be compliant with both versions
    				$locationId = $warehouseStock['warehouseID'];
    				if (!isset($locationId)){ //v2 api
    					$locationId = $warehouseStock['InventoryLocationID'];
    				}
    				$inventory = $this->_inventoriesLoader->create()->load($locationId, 'megaventory_id');
    				
    				$inventoryId = $inventory->getData('id');
    				
    				$this->updateInventoryProductStock($pId,$inventoryId,$inventoryStockData);
    					
    				$countsInTotalStock = $inventory->getCounts_in_total_stock();
    				
    				if ($countsInTotalStock == '1')
    				{
    					if ($configValue == '0') //no decrease value when order is placed
    						$totalStock += $inventoryStockData['stockqty'];
    					else //decrease stock when order is placed
    					{
    						$totalStock += $inventoryStockData['stockqty']-$inventoryStockData['stocknonshippedqty']-$inventoryStockData['stocknonallocatedwoqty'];
    					}
    				}
    			}
    			   			
    			$stockItem = $this->_stockItemFactory->create()->load($pId,'product_id');
    		
    			$stockItem->setQty($totalStock);
    			if ($totalStock > $stockItem->getMinQty())
    				$stockItem->setData('is_in_stock',1);
    			
    			$stockItem->save();
    		}
    	}
    }
    
    public function initializeInventoryLocations($apikey = -1, $apiurl = -1)
    {
    	if ($apikey  != -1)
    		$key = $apikey;
    	else
    		$key = $this->APIKEY;
    		
    	$data = array
    	(
    			'APIKEY' => $key
    	);
    
    		
    
    	try{
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'InventoryLocationGet',0, $apiurl);
    	}
    	catch (\Exception $ex){
    		return -1;
    	}
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode != '0')
    		return -1;
    
    	$mvInventoryLocations = $json_result['mvInventoryLocations'];
    
    	$i = 0;
    	$result = -1;
    	$mvIds = array();
    	foreach($mvInventoryLocations as $mvInventory)
    	{
    		if ($i == 0)
    			$this->insertInventory($mvInventory,true);
    		else
    			$this->insertInventory($mvInventory);
    			
    		$i++;
    	}
    
    	if ($i>0)
    		return $i;
    
    	return $result;
    }
    
    public function createMainInventory(){
    	$data = array
    	(
    			'APIKEY' => $this->APIKEY,
    			'mvInventoryLocation' => array(
    					'InventoryLocationID' => '0',
    					'InventoryLocationName' => 'Main Inventory',
    					'InventoryLocationAbbreviation' => 'Main',
    					'InventoryLocationAddress' => '',
    					'InventoryLocationCurrencyCode' => ''
    			),
    			'mvRecordAction' => 'Insert'
    	);
    
    	try{
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'InventoryLocationUpdate',0);
    	}
    	catch (\Exception $ex){
    		return 'There was a problem connecting to your Megaventory account. Please try again.';
    	}
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode != '0')
    		return $json_result['ResponseStatus']['Message'];
    
    	$this->insertInventory($json_result['mvInventoryLocation'],true);
    
    	return true;
    }
    
    public function updateInventoryProductStock($productId, $inventoryId, $stockData,$parentId = false){
    
    	if (isset($productId) && isset($inventoryId))
    	{
    		$productStock = $this->_productstocksLoader->create()
    		->loadInventoryProductstock($inventoryId, $productId);
    
    		$productStock->setProduct_id($productId);
    		$productStock->setInventory_id($inventoryId);
    		$productStock->setStockqty($stockData['stockqty']);
    		$productStock->setStockqtyonhold($stockData['stockqtyonhold']);
    		$productStock->setStockalarmqty($stockData['stockalarmqty']);
    		$productStock->setStocknonshippedqty($stockData['stocknonshippedqty']);
    		$productStock->setStocknonreceivedqty($stockData['stocknonreceivedqty']);
    		$productStock->setStockwipcomponentqty($stockData['stockwipcomponentqty']);
    		$productStock->setStocknonreceivedwoqty($stockData['stocknonreceivedwoqty']);
    		$productStock->setStocknonallocatedwoqty($stockData['stocknonallocatedwoqty']);
    		if ($parentId != false)
    			$productStock->setParent_id($parentId);
    			
    		$productStock->save();
    			
    		return true;
    	}
    
    	return false;
    }
    
    public function updateInventoryLocations($apikey = -1, $apiurl = -1)
    {
    	if ($apikey  != -1)
    		$key = $apikey;
    	else
    		$key = $this->APIKEY;
    		
    	$data = array
    	(
    			'APIKEY' => $key
    	);
    
    	try{
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'InventoryLocationGet',0, $apiurl);
    	}
    	catch (\Exception $ex){
    		return -1;
    	}
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode != '0')
    		return -1;
        
    	$mvInventoryLocations = $json_result['mvInventoryLocations'];
    
    	$i = 0;
    	$result = -1;
    	$mvIds = array();
    	foreach($mvInventoryLocations as $mvInventory)
    	{
    		$inventory = $this->checkIfInventoryExists($mvInventory);
    		if ($inventory == false){
    			$this->insertInventory($mvInventory);
    		}
    		else
    			$this->updateInventory($inventory, $mvInventory);
    			
    		$mvIds[] = $mvInventory['InventoryLocationID'];
    		$i++;
    	}
    
    	if (count($mvIds) > 0)
    		$this->deleteNotExistentInventories($mvIds);
    
    	if ($i>0)
    		return $i;
    
    	return $result;
    }   

    public function updateCountsInStock($inventoryId, $bCount){
    	$inventory = $this->_inventoriesLoader->create()->load($inventoryId);
    	$bCount == 'true' ? $countsInStock = '1' : $countsInStock = '0';
    	$inventory->setCounts_in_total_stock($countsInStock);
    	$inventory->save();
    }
    
    public function makeDefaultInventory($inventoryId){
    	if (isset($inventoryId)){

    		$connection = $this->_resource->getConnection();
    		$tableName = $this->_resource->getTableName('megaventory_inventories');
    
    		$noDefault = 'update '.$tableName.' set isdefault = 0';
    		$connection->query($noDefault);
    		$makeefault = 'update '.$tableName.' set isdefault = 1, counts_in_total_stock = 1 where id = '.$inventoryId;
    		$connection->query($makeefault);
    	}
    }
    
    public function updateInventoryProductAlertValue($productId, $inventoryId, $alertValue){
    
    	if (isset($productId) && isset($inventoryId))
    	{
    		$productStock = $this->_productstocksLoader->create()
    		->loadInventoryProductstock($inventoryId, $productId);
    			
    		$productStock->setProduct_id($productId);
    		$productStock->setInventory_id($inventoryId);
    		$productStock->setStockalarmqty($alertValue);
    		$productStock->save();
    			
    		$stockItem = $this->_stockItemFactory->create()->load($productId,'product_id');
    			
    		$productStockCollection = $this->_productstocksLoader->create()->loadProductstocks ($productId);
    			
    		$totalAlertQuantity = 0;
    		foreach ( $productStockCollection as $key => $productStock ) {
    			$inventoryAlertQty = $productStock ['stockalarmqty'];
    				
    			$inventory = $this->_inventoriesLoader->create()->load ( $inventoryId );
    			if ($inventory == false)
    				continue;
    				
    			if ($inventory->getCounts_in_total_stock () == '1') {
    				$totalAlertQuantity += $inventoryAlertQty;
    			}
    		}
    			
    		//update notify quantity
    		$useConfigNotify = $stockItem->getData('use_config_notify_stock_qty');
    		$configValue = $this->_scopeConfig->getValue('cataloginventory/item_options/notify_stock_qty');
    			
    		if ($useConfigNotify == '1'){
    			if (isset($configValue)){
    				if ($configValue != $totalAlertQuantity){
    					$stockItem->setData('use_config_notify_stock_qty',0);
    				}
    			}
    		}
    		else
    		{
    			if (isset($configValue)){
    				if ($configValue == $totalAlertQuantity){
    					$stockItem->setData('use_config_notify_stock_qty',1);
    				}
    			}
    		}
    			
    		$stockItem->setData('notify_stock_qty',$totalAlertQuantity);
    		$stockItem->save();
    		//end of notify quantity
    			
    		return array(
    				'totalAlertQuantity' => $totalAlertQuantity,
    				'isConfig' => ($totalAlertQuantity == $configValue) ? true : false
    		);
    	}
    
    	return array(
    			'totalAlertQuantity' => 0,
    			'isConfig' => false
    	);
    }
    
    private function checkIfInventoryExists($mvInventory){
    
    	$inventory = $this->_inventoriesLoader->create()->load($mvInventory['InventoryLocationID'], 'megaventory_id');
    	
    	$id = $inventory->getData('id');
    	if (!isset($id))
    		return false;
    
    	return $inventory;
    }
    
    private function insertInventory($mvInventory,$default = false){
    	$mvInventoryLocationID = $mvInventory['InventoryLocationID'];
    	$mvInventoryLocationName = $mvInventory['InventoryLocationName'];
    	$mvInventoryLocationAbbreviation = $mvInventory['InventoryLocationAbbreviation'];
    	$mvInventoryLocationAddress = $mvInventory['InventoryLocationAddress'];
    	
    	$connection = $this->_resource->getConnection();
    	$tableName = $this->_resource->getTableName('megaventory_inventories' );    	
    	
    	if ($default == false)
    		$sql_insert = 'insert into '.$tableName.' (name, shortname, address,megaventory_id, counts_in_total_stock) values ("'.$mvInventoryLocationName.'","'.$mvInventoryLocationAbbreviation.'","'.$mvInventoryLocationAddress.'","'.$mvInventoryLocationID.'","0")';
    	else
    		$sql_insert = 'insert into '.$tableName.' (name, shortname, address,megaventory_id, isdefault) values ("'.$mvInventoryLocationName.'","'.$mvInventoryLocationAbbreviation.'","'.$mvInventoryLocationAddress.'","'.$mvInventoryLocationID.'","1")';
    	
    	$connection->query($sql_insert);
    }
    
    private function updateInventory($inventory, $mvInventory){
    	$inventory->setData('shortname',$mvInventory['InventoryLocationAbbreviation']);
    	$inventory->setData('name',$mvInventory['InventoryLocationName']);
    	$inventory->setData('address',$mvInventory['InventoryLocationAddress']);
    	$inventory->setData('InventoryLocationAddress', $mvInventory['InventoryLocationAddress']);
    	$inventory->save();
    
    }
    
    private function deleteNotExistentInventories($mvIds)
    {
    	$connection = $this->_resource->getConnection();
    	$tableName = $this->_resource->getTableName('megaventory_inventories' );
    	
    	$sqlDelete = 'delete from '.$tableName.' where megaventory_id not in ('.implode(',', $mvIds).')';
    	$connection->query($sqlDelete);
    
    	$inventory = $this->_inventoriesLoader->create()->loadDefault();
    
    	if (!$inventory ->getId() && count($mvIds) > 0) { //there is no default inventory
    		$newDefaultInventory = $this->_inventoriesLoader->create()->load($mvIds[0],'megaventory_id');
    		$newDefaultInventory->setData('isdefault','1');
    		$newDefaultInventory->setData('counts_in_total_stock','1');
    		$newDefaultInventory->save();
    	}
    }
    
    public function getIdByMegaventoryId($mvId)
    {
    	$connection = $this->_resource->getConnection();
    	$tableName = $this->_resource->getTableName('catalog_product_entity');
    	
    	$productId = $connection->fetchOne('select entity_id from '.$tableName.' where mv_product_id = '.$mvId);
    	
    	return $productId;
    }
}
