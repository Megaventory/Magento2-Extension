<?php

namespace Mv\Megaventory\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	private $MEGAVENTORY_API_URL;
	
	protected $_scopeConfig;
    private $_resource;
        
	protected $logger;
	protected $mvLogFactory;
	private $_progressMessage; 
	
	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\App\ResourceConnection $recource,
		\Mv\Megaventory\Model\LogFactory $mvLogFactory,
		\Mv\Megaventory\Logger\Logger $logger
    ) {
		$this->_scopeConfig = $scopeConfig; 
        $this->_resource = $recource;
                        
		$this->mvLogFactory = $mvLogFactory;
		$this->logger = $logger;
        parent::__construct($context);
    }
	
	public function makeJsonRequest($data, $action, $magentoId = 0, $apiurl = -1, $enabled = -1){
		//if (empty(self::$MEGAVENTORY_API_URL))
		$this->MEGAVENTORY_API_URL = $this->_scopeConfig->getValue('megaventory/general/apiurl');
		
		if ($apiurl != -1)
			$this->MEGAVENTORY_API_URL = $apiurl;
		
		if ($enabled == -1)
			$megaventoryIntegration = $this->_scopeConfig->getValue('megaventory/general/enabled');
		else
			$megaventoryIntegration = $enabled;
		
		if ($megaventoryIntegration == '1'){
			$this->logger->info('action = '.$action);
			$data_string = json_encode ( $data );
			$this->logger->info('data = '.$data_string);
			
			
			$ch = curl_init ($this->MEGAVENTORY_API_URL.$action);
			curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			
			// letrim check in production if we need it
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
			// end of letrim
			
			curl_setopt ( $ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json', 'Content-Length: ' . strlen ( $data_string ) ) );
			$Jsonresult = curl_exec ( $ch );
			
			$curlError = curl_error ( $ch );

			$json_result = json_decode ( $Jsonresult, true );
			$this->logger->info($Jsonresult);
			$test = json_last_error();
			if ($test != JSON_ERROR_NONE){
				$event = array(
							'code' => $action,
							'result' => 'json fail',
							'magento_id' => '0',
							'return_entity' => '0',
							'details' => $test,
							'data' => $data_string
							//'data' => serialize($data)
					);
					$this->log($event);
			} 
			else
			{
				$errorCode = $json_result['ResponseStatus']['ErrorCode'];
				if ($errorCode != '0'){//log errors
					
					//do not log gets
					if (strpos($action,'Get') === false){
						$event = array(
									'code' => $action,
									'result' => 'fail',
									'magento_id' => $magentoId,
									'return_entity' => '0',
								    'details' => $json_result['ResponseStatus']['Message'],
									'data' => $data_string
									//'data' => serialize($data)
								);
							$this->log($event);
					}
				}
			}
			return $json_result;
		}
		else
		{
			return false;
		}
	}
	
	public function sendProgress($gid, $message, $progress, $step, $addToReport = false)
	{
		//static $id = 1;
		static $headingid = 1;
		static $detailid = 1;
		static $flag = 0;
		static $lastStep = '';
	
		$id = $gid;
		
		if ($addToReport != false)
			$this->_progressMessage .= '<br/>'.$message;
	
		$d = array('message' => $message , 'progress' => $progress, 'step' => $step);
		$messageData = serialize($d);
		
		$write = $this->_resource->getConnection();
		$tableName = $this->_resource->getTableName('megaventory_progress' );
		
		if ($lastStep != $step && (empty($progress) || $progress == 1)){
			$sqlCmd = 'insert into '.$tableName.' (id, messagedata,type) values ('.$id.',\''.$messageData.'\',\'heading\')';
			$headingid = $id;
			$id++;
			$flag = 0;
			$lastStep = $step;
		}
		else
		{
			if ($flag == 0 && $progress == 1){
				$sqlCmd = 'insert into '.$tableName.' (id,messagedata,type) values ('.$id.',\''.$messageData.'\',\'details\')';
				$detailid = $id;
				$id++;
				$flag = 1;
			}
			else{
				if ($progress > 1)
					$detailid = $gid;
	
				$sqlCmd = 'update '.$tableName.' set messagedata = \''.$messageData.'\' where type = \'details\' and id = '.$detailid;
			}
	
		}
	
		$write->query($sqlCmd);
	
	}
	
	public function getProgressMessage(){
		return $this->_progressMessage;
	}
	
	public function resetMegaventoryData(){
	
		$write = $this->_resource->getConnection();
	
		$deleteInventories = 'delete from '.$this->_resource->getTableName('megaventory_inventories');
		$write->query($deleteInventories);
		$deleteTaxes = 'delete from '.$this->_resource->getTableName('megaventory_taxes');
		$write->query($deleteTaxes);
		$deleteCurrencies = 'delete from '.$this->_resource->getTableName('megaventory_currencies');
		$write->query($deleteCurrencies);
		$deleteStock = 'delete from '.$this->_resource->getTableName('megaventory_stock');
		$write->query($deleteStock);
		$deleteLog = 'delete from '.$this->_resource->getTableName('megaventory_log');
		$write->query($deleteLog);
		$deleteProgress = 'delete from '.$this->_resource->getTableName('megaventory_progress');
		$write->query($deleteProgress);
	
		$updateCustomer = 'update '.$this->_resource->getTableName('customer_entity').' set mv_supplierclient_id = NULL';
		$write->query($updateCustomer);
		$updateCategory= 'update '.$this->_resource->getTableName('catalog_category_entity').' set mv_productcategory_id = NULL';
		$write->query($updateCategory);
		$updateProduct = 'update '.$this->_resource->getTableName('catalog_product_entity').' set mv_product_id = NULL';
		$write->query($updateProduct);
	
		$updateSalesFlatOrder = 'update '.$this->_resource->getTableName('sales_order').' set mv_salesorder_id = NULL, mv_inventory_id = 0';
		$write->query($updateSalesFlatOrder);
		$updateSalesFlatOrderGrid = 'update '.$this->_resource->getTableName('sales_order_grid').' set mv_inventory_id = 0';
		$write->query($updateSalesFlatOrderGrid);
	}
	
	public function getMegaventoryAccountSettings($settingName=false,$apikey = false, $apiurl = -1){
		if ($apikey != false)
			$key = $apikey;
		else
			$key = $this->_scopeConfig->getValue('megaventory/general/apikey');
	
		$data = array
		(
				'APIKEY' => $key,
				'SettingName' => ($settingName === false) ? 'All' : $settingName
		);
			
	
		$json_result = $this->makeJsonRequest($data ,'AccountSettingsGet',0,$apiurl);
	
		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
		if ($errorCode != '0')
			return false;
	
	
		return $json_result['mvAccountSettings'];
	}
	
	public function checkConnectivity(){

		$mvIntegration = $this->_scopeConfig->getValue('megaventory/general/enabled');
		if ($mvIntegration != '1')
		{
			return 'Megaventory extension is disabled';
		}
		
		$accountSettings = $this->getMegaventoryAccountSettings();
	
		if ($accountSettings === false) //connectivity problem
			return 'There is a problem with your megaventory credentials!';
		else
		{
			$message = '';
			$magentoInstallationsIsSet = false;
			foreach ($accountSettings as $index => $accountSetting) {
				$settingName = $accountSetting['SettingName'];
				$settingValue = $accountSetting['SettingValue'];
				
				if ($settingName == 'isOrdersModuleEnabled' && $settingValue == false)
					$message .= 'Ordering module in Megaventory is not enabled.';
	
				if ($settingName == 'MagentoInstallations')
					$magentoInstallationsIsSet = true;
	
				if ($settingName == 'MagentoInstallations' && $settingValue == 0)
					$magentoInstallationsIsSet = false;
	
	
			}
			if (!$magentoInstallationsIsSet){
				$message .= "You haven't set in Megaventory the number of your active Magento installations.";
			}
				
			if (strlen($message) > 0){
				return $message;
			}
		}
	
		return true;
	}
	
	public function log($event){
		if ($event['result'] != 'success'){
			$newMvLog = $this->mvLogFactory->create();
			$newMvLog->setData($event);
			$newMvLog->save();
		}
	}
}
