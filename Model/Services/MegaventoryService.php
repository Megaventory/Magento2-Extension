<?php

namespace Mv\Megaventory\Model\Services;

class MegaventoryService 
{
	const STOCK_CONSUME_LIMIT = 50;
	
	protected $_scopeConfig;
	protected $_mvHelper;
	protected $_commonHelper;
	protected $_productFactory;
	protected $_inventoriesFactory;
	protected $_inventoriesHelper;
	protected $_productStocksFactory;
	protected $_stockItemFactory;
	
	protected $_orderApi;
	protected $_orderFactory;
	protected $_orderStatusHistoryFactory;
	
	protected $_shipmentRepository;
	protected $_shipmentFactory;
	protected $_shipmentCommentFactory;
	protected $_shipmentSender;
	protected $_shipmentNotifier;
	protected $_trackFactory;
	
	protected $_invoiceService;
	protected $_invoiceRepository;
	
	protected $_transaction;
	
	protected $_logger;
	protected $mvLogFactory;
	
	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Mv\Megaventory\Helper\Data $mvHelper,
		\Mv\Megaventory\Helper\Common $commonHelper,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Mv\Megaventory\Model\InventoriesFactory $inventoriesFactory,
		\Mv\Megaventory\Helper\Inventories $inventoriesHelper,
		\Mv\Megaventory\Model\ProductstocksFactory $productStocksFactory,
		\Magento\CatalogInventory\Model\Stock\ItemFactory $stockItemFactory,
		\Magento\Sales\Api\OrderManagementInterface $orderApi,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Sales\Model\Order\Status\HistoryFactory $orderStatusHistoryFactory,
		\Magento\Sales\Model\Service\InvoiceService $invoiceService,
		\Magento\Sales\Model\Order\InvoiceRepository $invoiceRepository,
		\Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
		\Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
		\Magento\Sales\Model\Order\Shipment\CommentFactory $shipmentCommentFactory,
		\Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
		\Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
		\Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
		\Magento\Framework\DB\Transaction $transaction,
		\Mv\Megaventory\Model\LogFactory $mvLogFactory,
		\Mv\Megaventory\Logger\Logger $logger
	){
		$this->_scopeConfig = $scopeConfig;
		
		$this->_mvHelper = $mvHelper;
		$this->_commonHelper = $commonHelper;
		$this->_productFactory = $productFactory;
		$this->_inventoriesFactory = $inventoriesFactory;
		$this->_inventoriesHelper = $inventoriesHelper;
		$this->_productStocksFactory = $productStocksFactory;
		$this->_stockItemFactory = $stockItemFactory;
		
		$this->_orderApi = $orderApi;
		$this->_orderFactory = $orderFactory;
		$this->_orderStatusHistoryFactory = $orderStatusHistoryFactory;
		
		$this->_shipmentRepository = $shipmentRepository;
		$this->_shipmentFactory = $shipmentFactory;
		$this->_shipmentCommentFactory = $shipmentCommentFactory;
		$this->_shipmentSender = $shipmentSender;
		$this->_shipmentNotifier = $shipmentNotifier;
		$this->_trackFactory = $trackFactory;
		
		$this->_invoiceService = $invoiceService;
		$this->_invoiceRepository = $invoiceRepository;
		
		$this->_transaction = $transaction;
		
		$this->_mvLogFactory = $mvLogFactory;
		$this->_logger = $logger;
	}
	
	public function applyPendingUpdates(){
		$this->_logger->info('update run');
	
		$key = $this->_scopeConfig->getValue('megaventory/general/apikey');
	
		$magentoId = $this->_scopeConfig->getValue('megaventory/general/magentoid');
	
		if (!isset($magentoId))
			$magentoId = "magento";
			
		$data = array
		(
				'APIKEY' => $key,
				'Filters' => array(
								"AndOr" => "And",
								"FieldName" => "Application",
								"SearchOperator" => "Equals",
								"SearchValue" => $magentoId
							 )
		);
			
	
		$json_result = $this->_mvHelper->makeJsonRequest($data ,'IntegrationUpdateGet',0);
	
	
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0'){
			$this->_logger->info('error');
			$this->_logger->info($errorCode);
			$this->_logger->info($json_result['ResponseStatus']['Message']);
			return;
		}
	
		$mvIntegrationUpdates = $json_result['mvIntegrationUpdates'];
	
		foreach($mvIntegrationUpdates as $mvIntegrationUpdate)
		{
			$result = false;
				
			$this->_logger->info('integration update id : '.$mvIntegrationUpdate['IntegrationUpdateID']);
			$entityIDs = explode('##$', $mvIntegrationUpdate['EntityIDs']);
				
			$mvIntegrationUpdateId = $mvIntegrationUpdate['IntegrationUpdateID'];
			$tries = $mvIntegrationUpdate['Tries'];
	
			//delete if failed more than 10 times
			if ($tries > 10){
				$this->deleteUpdate($mvIntegrationUpdateId);
			}
	
			if ($mvIntegrationUpdate['Entity'] == 'product'){
				$product = json_decode($mvIntegrationUpdate['JsonData'],true);
	
				if ($mvIntegrationUpdate['Action'] == 'update'){
	
				}
				else if ($mvIntegrationUpdate['Action'] == 'delete'){
	
				}
				else if ($mvIntegrationUpdate['Action'] == 'insert'){
	
				}
			}
			else if ($mvIntegrationUpdate['Entity'] == 'sales_order'){
				try{
					for ($i = 0; $i < count($entityIDs);$i++){
						$orderIncrementId = $entityIDs[$i];
						$order = $this->_orderFactory->create()->load($orderIncrementId,'increment_id');
							
						if ($mvIntegrationUpdate['Action'] == 'complete'){
							$ordeStatusHistory = $this->_orderStatusHistoryFactory->create();
							$ordeStatusHistory->setStatus('complete');
							$ordeStatusHistory->setComment('');
							$ordeStatusHistory->setIsCustomerNotified(false);
							$result = $this->_orderApi->addComment($order->getId(), $ordeStatusHistory);
								
							if ($result){
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'cancel'){
							$result = $this->_orderApi->cancel($order->getId());
								
							if ($result){
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'invoice'){
							if ($order->canInvoice()) {
								$newInvoice = $this->_invoiceService->prepareInvoice($order);
								
								$newInvoice->register();
								
								$newInvoice->getOrder()->setCustomerNoteNotify(false);
					            $newInvoice->getOrder()->setIsInProcess(true);
					
					            $this->_transaction
					            	->addObject($newInvoice)
					            	->addObject($newInvoice->getOrder());
					            
					            $this->_transaction->save();
									
								$invoiceIncrementId = $newInvoice->getIncrementId();
		
								if ($newInvoice->canCapture()) {
									$newInvoice->capture();
								}
		
								$result = $invoiceIncrementId;
								if ($result)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else
								{
									if ($tries > 10)
										$this->deleteUpdate($mvIntegrationUpdateId);
									else{
										$mvIntegrationUpdate['Tries'] = $tries+1;
										$mvIntegrationUpdate['payload'] = $result;
										$this->updateIntegrationUpdate($mvIntegrationUpdate);
									}
								}
							}
							else{
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'ship'){
							$jsonData = $mvIntegrationUpdate['JsonData'];
							$extraShippingInformation = json_decode($jsonData, true);
							
							if ($order->canShip()) {
								$newShipment = $this->_shipmentFactory->create($order,[]);
								$newShipment->register();
								//$newShipment->getOrder()->setIsInProcess(true);
								
								
								$this->_transaction
									->addObject($newShipment)
									->addObject($newShipment->getOrder())
									->save();
	
								if ($extraShippingInformation['Notify'] == '1'){//then also send a shipment email
									$this->_shipmentSender->send($newShipment);
								}
								
								$result = $newShipment->getIncrementId();
								if ($result){
									$this->deleteUpdate($mvIntegrationUpdateId);
								}
								else
								{
									if ($tries > 10)
										$this->deleteUpdate($mvIntegrationUpdateId);
									else{
										$mvIntegrationUpdate['Tries'] = $tries+1;
										$mvIntegrationUpdate['payload'] = $result;
										$this->updateIntegrationUpdate($mvIntegrationUpdate);
									}
								}
							}
							else 
							{
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'track'){
							$jsonData = $mvIntegrationUpdate['JsonData'];
	
							$result = false;
	
							$shipmentIncrementId= "";
								
							$orderId = $order->getId();
	
							$orderFilter = new \Magento\Framework\Api\Filter();
							$orderFilter->setField('order_id');
							$orderFilter->setValue($orderId);
							$orderFilter->setConditionType('eq');
							
							$filterGroup = new \Magento\Framework\Api\Search\FilterGroup();
							$filterGroup->setFilters(array($orderFilter));
							
							$searchCriteria = new \Magento\Framework\Api\SearchCriteria();
							$searchCriteria->setFilterGroups(array($filterGroup));
							$shipmentResult = $this->_shipmentRepository->getList($searchCriteria);
							
							if ($shipmentResult->getTotalCount() > 0 )
							{
								$shipment = current($shipmentResult->getItems());
								$shipmentIncrementId = $shipment->getIncrementId();
							}
	
							if ($jsonData && !empty($shipmentIncrementId))
							{
								$trackingInformation = json_decode($jsonData, true);
								$result = $this->megaventoryAddTrack($shipment, 'custom', $trackingInformation['ShippingProviderName'],
										$trackingInformation['TrackNumber'],$trackingInformation['Notify']);
							}
							if ($result){
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
						else if ($mvIntegrationUpdate['Action'] == 'partially_process'){
							$ordeStatusHistory = $this->_orderStatusHistoryFactory->create();
							$ordeStatusHistory->setStatus('processing');
							$ordeStatusHistory->setComment('');
							$ordeStatusHistory->setIsCustomerNotified(false);
							$result = $this->_orderApi->addComment($order->getId(), $ordeStatusHistory);
								
							if ($result) {
								$this->deleteUpdate($mvIntegrationUpdateId);
							}
							else
							{
								if ($tries > 10)
									$this->deleteUpdate($mvIntegrationUpdateId);
								else{
									$mvIntegrationUpdate['Tries'] = $tries+1;
									$mvIntegrationUpdate['payload'] = $result;
									$this->updateIntegrationUpdate($mvIntegrationUpdate);
								}
							}
						}
					}
				}
				catch (\Exception $e){
					$this->_logger->debug('exception message: '.$e->getMessage());
					$mvIntegrationUpdate['Tries'] = $tries+1;
					$this->updateIntegrationUpdate($mvIntegrationUpdate);
				}
			}
			else if ($mvIntegrationUpdate['Entity'] == 'stock'){
				try{
					$inventoryValues = json_decode($mvIntegrationUpdate['JsonData']);
					
					if (count($entityIDs) > MegaventoryService::STOCK_CONSUME_LIMIT ){
						$newEntityIds = array_slice($entityIDs, 0, MegaventoryService::STOCK_CONSUME_LIMIT);
						$remainingEntityIds = array_slice($entityIDs, MegaventoryService::STOCK_CONSUME_LIMIT);
						$newInventoryValues = array_slice($inventoryValues, 0, MegaventoryService::STOCK_CONSUME_LIMIT);
						$remainingInventoryValues = array_slice($inventoryValues, MegaventoryService::STOCK_CONSUME_LIMIT);
					
						$result = $this->updateMegaventoryStock($newEntityIds, $newInventoryValues, $mvIntegrationUpdateId);
					
						if ($result)
						{
							$remainingEntityIds = implode('##$', $remainingEntityIds);
							$mvIntegrationUpdate['EntityIDs'] = $remainingEntityIds;
							$mvIntegrationUpdate['JsonData'] = json_encode($remainingInventoryValues);
							$this->updateIntegrationUpdate($mvIntegrationUpdate);
						}
						continue;
					}
					
					$result = $this->updateMegaventoryStock($entityIDs, $inventoryValues, $mvIntegrationUpdateId);
						
	
					if ($result)
						$this->deleteUpdate($mvIntegrationUpdateId);
					else
					{
						$mvIntegrationUpdate['Tries'] = $tries+1;
						$mvIntegrationUpdate['payload'] = $result;
						$this->updateIntegrationUpdate($mvIntegrationUpdate);
					}
				}
				catch (\Exception $e){
					$this->_logger->debug('exception message: '.$e->getMessage());
					$mvIntegrationUpdate['Tries'] = $tries+1;
					$this->updateIntegrationUpdate($mvIntegrationUpdate);
				}
			}
			else { //NOT HANDLED UPDATE SHOULD BE DELETED
				try{
					$this->deleteUpdate($mvIntegrationUpdateId);
				}
				catch (\Exception $e){
					$this->_logger->debug('exception message: '.$e->getMessage());
				}
			}
		}
	}
	
	private function deleteUpdate($mvIntegrationUpdateId){
	
		$key = $this->_scopeConfig->getValue('megaventory/general/apikey');
	
		$data = array
		(
				'APIKEY' => $key,
				'IntegrationUpdateIDToDelete' => $mvIntegrationUpdateId
		);
	
	
		$json_result = $this->_mvHelper->makeJsonRequest($data ,'IntegrationUpdateDelete',0);
	}
	
	private function updateIntegrationUpdate($mvIntegrationUpdate){
	
		$key = $this->_scopeConfig->getValue('megaventory/general/apikey');
	
		$data = array
		(
				'APIKEY' => $key,
				'mvIntegrationUpdate' => $mvIntegrationUpdate,
				'mvRecordAction' => 'Update'
		);
	
		$json_result = $this->_mvHelper->makeJsonRequest($data ,'IntegrationUpdateUpdate',0);
	}
	
	public function updateMegaventoryStock($productSKUs, $inventoryValues, $integratiodId = 0) {

		if (! $this->_commonHelper->isMegaventoryEnabled())
			return false;
	
		if (count ( $productSKUs ) != count ( $inventoryValues )) {
			return false;
		}
	
		$inventoryValues = ( array ) $inventoryValues;
		$json = json_encode ( $inventoryValues );
	
		$inventoryValues = json_decode ( $json, true );
		$totalStock = 0;
		$productIds = array ();
		foreach ( $productSKUs as $index => $productSKU ) {
			
			if (empty($productSKU)) continue;
			
			$megaventoryId = $inventoryValues [$index] ['inventory_id'];
			$stockData = $inventoryValues [$index] ['stock_data'];
			
			$productId = $this->_productFactory->create()->getIdBySku($productSKU);
			
			if (!is_array($stockData)) continue;
			
			$stockKeys = array_keys ( $stockData );
				
			if ($productId) {
		
				
				$inventory = $this->_inventoriesHelper->getInventoryFromMegaventoryId ( $megaventoryId );
	
				if ($inventory != false) {
					
					$this->_inventoriesHelper->updateInventoryProductStock ( $productId, $inventory->getId (), $stockData, false, $integratiodId );
					
					$productIds [] = $productId;
				}
			}
		}
	
		foreach ( $productIds as $pId ) {
			$productStockCollection = $this->_productStocksFactory->create()->loadProductstocks($pId);
			
			$totalStock = 0;
			$totalAlertQuantity = 0;
			foreach ( $productStockCollection as $key => $productStock ) {
				$inventoryStock = $productStock ['stockqty'];
				$inventoryNonShippedStock = $productStock ['stocknonshippedqty'];
				$inventoryNonAllocatedWOStock = $productStock ['stocknonallocatedwoqty'];
				$inventoryAlertQty = $productStock ['stockalarmqty'];
	
				$inventoryId = $productStock ['inventory_id'];
				
				$inventory = $this->_inventoriesFactory->create()->load($inventoryId);
				if ($inventory == false)
					continue;
	
				if ($inventory->getCounts_in_total_stock () == '1') {
					$configValue = $this->_scopeConfig->getValue( 'cataloginventory/options/can_subtract' );
					if ($configValue == '0') // no decrease value when order is
						// placed
						$totalStock += $inventoryStock;
					else 					// decrease stock when order is placed
					{
						$totalStock += $inventoryStock - $inventoryNonShippedStock - $inventoryNonAllocatedWOStock;
					}
						
					$totalAlertQuantity += $inventoryAlertQty;
				}
			}
			$stockItem = $this->_stockItemFactory->create()->load($pId,'product_id');
			
			$stockItem->setQty ( $totalStock );
			if ($totalStock > $stockItem->getMinQty ())
				$stockItem->setData ( 'is_in_stock', 1 );
				
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
			//end of notify quantity
				
			$stockItem->save ();
		
		}
	
		return true;
	}
	
	public function megaventoryAddTrack(\Magento\Sales\Model\Order\Shipment $shipment, $carrier, $title, $trackNumber,$notify)
	{
		if ($shipment) {
			
			$track = $this->_trackFactory->create();
			
			$track 
				->setNumber($trackNumber)
				->setCarrierCode($carrier)
				->setTitle($title);
			
			$shipment->addTrack($track);
			$shipment->save();
			$track->save();
			
			if ($notify == 1){
				$this->_shipmentNotifier->notify($shipment);
				$shipment->save();
			}
		}
		
		return $track->getId();
	}
}