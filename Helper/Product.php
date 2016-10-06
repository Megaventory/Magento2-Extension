<?php

namespace Mv\Megaventory\Helper;

use \Mv\Megaventory\Logger\Logger;
use \Mv\Megaventory\Model\LogFactory;
use \Magento\Framework\Filesystem\Io\Magento\Framework\Filesystem\Io;

class Product extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_scopeConfig;
    private $_mvHelper;
    private $_inventoriesHelper;
    private $_productLoader;
    private $_categoryLoader;
    private $_inventoriesLoader;
    private $_productStocksLoader;
    private $_bomLoader;
    private $_productCollectionFactory;
    private $_productHelper;
    private $_storeManager;
    private $_resource;
    private $_attributeFactory;
    private $_attributeSetFactory;
    private $_catalogProductTypeConfigurable;
    private $APIKEY;
    private $_registry;
	private $_backendUrl;
    
	protected $logger;
	protected $mvLogFactory;
	
	
	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		Data $mvHelper,
		\Mv\Megaventory\Helper\Inventories $inventoriesHelper,
        \Magento\Catalog\Model\ProductFactory $productLoader,
        \Magento\Catalog\Model\CategoryFactory $categoryLoader,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
        \Mv\Megaventory\Model\ProductstocksFactory $productStocksLoader,
        \Mv\Megaventory\Model\BomFactory $bomLoader,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Helper\Product $productHelper,
		\Magento\Store\Model\StoreManager $storeManager,
		\Magento\Framework\App\ResourceConnection $recource,
		\Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
		\Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
		\Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
    	\Magento\Framework\Registry $registry,
        \Magento\Backend\Model\UrlInterface $backendUrl,
		LogFactory $mvLogFactory,
		Logger $logger
    ) {
		$this->_scopeConfig = $scopeConfig; 
        $this->_mvHelper = $mvHelper;
        $this->_inventoriesHelper = $inventoriesHelper;
        $this->_productLoader = $productLoader;
        $this->_categoryLoader = $categoryLoader;
        $this->_inventoriesLoader = $inventoriesLoader;
        $this->_productStocksLoader = $productStocksLoader;
        $this->_bomLoader = $bomLoader;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productHelper = $productHelper;
        $this->_storeManager = $storeManager;
        $this->_resource = $recource;
        $this->_attributeFactory = $attributeFactory;
        $this->_attributeSetFactory = $attributeSetFactory;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        
        $this->_registry = $registry;
        $this->_backendUrl = $backendUrl;
		$this->mvLogFactory = $mvLogFactory;
		$this->logger = $logger;
		

		$this->APIKEY = $this->_scopeConfig->getValue('megaventory/general/apikey');
        parent::__construct($context);
    }
    
    public function getProduct($mvProductId)
    {
    	$data = array
    	(
    			'APIKEY' => $this->APIKEY,
    			'query' => 'mv.ProductID = '.$mvProductId
    	);
    
    
    	$json_result = $this->_mvHelper->makeJsonRequest($data ,'ProductGet',0);
    
    	if ($json_result['ResponseStatus']['ErrorCode'] == 0){
    		return $json_result['mvProducts'][0];
    	}
    	return -1;
    }
    
    public function addProduct($product){

    	$productType = $product->getType_id();
    	if ($productType == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE || $productType == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL)
    	{	
    		$productId = $product->getEntityId();
    	
    		$product = $this->_productLoader->create()->load($productId);
    	
    		$megaVentoryId = $product->getData('mv_product_id');
    		$name = $product['name'];
    		$sku = $product['sku'];
    		if (isset($product['weight']) == false)
    			$weight = '0';
    		else
    			$weight = $product['weight'];
    		 
    		$price = '0';
    		if (!empty($product['price']))
    			$price = $product['price'];

    		$finalPrice = $product->getPriceInfo()->getPrice('final_price')->getValue();
    		
    		$taxAdjustment = $product->getPriceInfo()->getAdjustment('tax');
    		
    		if ($taxAdjustment->isIncludedInBasePrice()) {
    			$adjust = $taxAdjustment->extractAdjustment($finalPrice, $product);
    			
    			$finalPriceNoTax = round($finalPrice - $adjust,2);
    		}
    		else 
    			$finalPriceNoTax = $finalPrice;
    		
    		$cost = '0';
    		if (!empty($product['cost']))
    			$cost = $product['cost'];
    		     		 
    		//pass supplier on the fly
    		$mvSupplierId = '';    		 
    		$supplierAttributeCode =  $this->_scopeConfig->getValue('megaventory/general/supplierattributecode');
    		if (isset($supplierAttributeCode)){
    			$attribute = $this->_attributeFactory->create()->loadByCode('catalog_product', $supplierAttributeCode);
    			
				$frontendInput = $attribute->getFrontendInput();
	
				$magentoSupplierId = $product->getData($supplierAttributeCode);
				
				if ($frontendInput == 'text')
					$supplierName = $magentoSupplierId;
				else if ($frontendInput == 'select')
					$supplierName = $product->getAttributeText($supplierAttributeCode);
				
				if (isset($magentoSupplierId) && ($frontendInput == 'text' || $frontendInput == 'select')){
					
					$this->logger->info('supplier name = '.$supplierName);
				
					if ($supplierName){
						
						$supplierData = array
						(
								'APIKEY' => $this->APIKEY,
								'query'=> 'mv.SupplierClientName = "'.$supplierName.'"'
						);
					
						$json_result = $this->_mvHelper->makeJsonRequest($supplierData ,'SupplierClientGet');
						
						$errorCode = $json_result['ResponseStatus']['ErrorCode'];
						if ($errorCode == '0'){//no errors
							
							//supplier exists
							if (count($json_result['mvSupplierClients']) > 0){
								$mvSupplierId = $json_result['mvSupplierClients'][0]['SupplierClientID'];
							}
							else //supplier is new
							{
								$supplierData = array
								(
										'APIKEY' => $this->APIKEY,
										'mvSupplierClient' => array (
												'SupplierClientID' => 0,
												'SupplierClientType' => '1',
												'SupplierClientName' => $supplierName,
												'SupplierClientBillingAddress' => '',
												'SupplierClientShippingAddress1' => '',
												'SupplierClientShippingAddress2' => '',
												'SupplierClientPhone1' => '',
												'SupplierClientPhone2' => '',
												'SupplierClientFax' => '',
												'SupplierClientIM' => '',
												'SupplierClientEmail' => '',
												'SupplierClientTaxID' => '',
												'SupplierClientComments' => ''
						 						),
										'mvRecordAction' => 'Insert' 
								);
				
								$json_result = $this->_mvHelper->makeJsonRequest($supplierData, 'SupplierClientUpdate',0);
								
								$errorCode = $json_result['ResponseStatus']['ErrorCode'];
								if ($errorCode == '0'){//no errors
									$mvSupplierId = $json_result['mvSupplierClient']['SupplierClientID'];
								}
							}
						}
					
						$this->logger->info('mv supplier id = '.$mvSupplierId);
					}
				}
    		}
    		
    		$version = '';
    		$parentIds = $this->_catalogProductTypeConfigurable->getParentIdsByChild($productId);
    		
    		if (isset($parentIds) && isset($parentIds[0]))
    		{
    			$parentProduct = $this->_productLoader->create()->load($parentIds[0]);
    			if (isset($parentProduct)){
    				$simpleProduct = $product;
    				$product = $parentProduct;
    					
    				$productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
    				$attributeOptions = array();
    				$attributeValues = array();
    		
    				foreach ($productAttributeOptions as $productAttribute) {
    					//$attributeOptions[$productAttribute['attribute_code']] = $productAttribute['store_label'];
    		
    					foreach ($productAttribute['values'] as $attribute) {
    						if ($attribute['value_index'] == $simpleProduct[$productAttribute['attribute_code']])
    						{
    							$attributeValues[$productAttribute['store_label']] = $attribute['label'];
    							break;
    						}
    						//$attributeOptions[$productAttribute['label']][$attribute['value_index']] = $attribute['store_label'];
    					}
    				}
    				foreach ($attributeValues as $Key => $Value)
    					$version .= $Key . ':' . $Value . ';';
    			}
    		}
    		
    		
    		$shortDescription = '';
    		if (isset($product['short_description'])){
    			$shortDescription = $product['short_description'];
    			if (strlen($shortDescription) > 400)
    				$shortDescription = mb_substr($shortDescription,0,400, "utf-8");
    				//$shortDescription = substr($shortDescription, 0, 400);
    		}
    		$description = '';
    		if (isset($product['description']))
    		{
    			$description = $product['description'];
    			if (strlen($description) > 400)
    				$description = mb_substr($description,0,400, "utf-8");    				
    				//$description = substr($description, 0, 400);
    		}
    		
    		try{
    			 $image = $this->_productHelper->getImageUrl($product);
    			 //$this->_imagetHelper->init($product, 'product_base_image');
    		}
    		catch(\Exception $e)
    		{
    			$image = '';
    		}
    		
    		if(isset($megaVentoryId) && $megaVentoryId!=NULL) //it is an update
    		{
    			$mvProductId = $megaVentoryId;
    			$mvRecordAction = 'Update';
    		}
    		else //it is an insert
    		{
    			$mvProductId = '0';
    			$mvRecordAction = 'Insert';
    		}
    		
    		$categoryIds = $product->getCategoryIds();
    		$mvCategoryId = '0';
    		if (is_array($categoryIds)) {
    			//randomly choose the first category
    			if (isset($categoryIds[0])){
					$category = $this->_categoryLoader->create()->load($categoryIds[0]);
    				$categoryId = $category->getEntityId();
    					
    				$mvCategoryId = $category->getData('mv_productcategory_id');
    		
    				//if user adds a product that belongs to an unsynced category
    				//megaventory then insert it to megaventory as orphan
    				if (isset($mvCategoryId) == false || $mvCategoryId == NULL){
    					$mvCategoryId = '0';
    				}
    			}
    		}
    		
    		
    		$attibuteSetId = $product->getAttributeSetId();
    		$attributeSetName = $this->_attributeSetFactory->create()->load($attibuteSetId)->getAttributeSetName();
    		
    		//prepare data
    		$data = array
    		(
    				'APIKEY' => $this->APIKEY,
    				'mvProduct'=> array
    				(
    						'ProductID' => $mvProductId,
    						'ProductType' => "BuyFromSupplier",
    						'ProductSKU' => $sku,
    						'ProductEAN' => '', //$product['ean'],
    						'ProductDescription' => $name,
    						'ProductVersion' => $version, //$product['version'],
    						'ProductLongDescription' => $shortDescription,
    						'ProductCategoryID' => $mvCategoryId,
    						'ProductUnitOfMeasurement'=>'Unit(s)',
    						'ProductSellingPrice'=>$finalPriceNoTax,
    						'ProductPurchasePrice'=>$cost,
    						'ProductWeight'=>$weight,
    						'ProductLength'=>'0',
    						'ProductBreadth'=>'0',
    						'ProductHeight'=>'0',
    						'ProductImageURL'=>$image,
    						'ProductComments'=>'',
    						'ProductCustomField1'=>$attributeSetName,
    						'ProductCustomField2'=>'',
    						'ProductCustomField3'=>'',
    						'ProductMainSupplierID'=>$mvSupplierId,
    						'ProductMainSupplierPrice'=>'0',
    						'ProductMainSupplierSKU'=>'',
    						'ProductMainSupplierDescription'=>'',
    							
    				),
    				'mvRecordAction'=>$mvRecordAction
    		);
    		 
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'ProductUpdate',$productId);
    		
    		
    		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    		if ($errorCode == '0'){//no errors
    			if (strcmp('Insert', $mvRecordAction) == 0){
    				$this->updateProduct($productId,$json_result['mvProduct']['ProductID']);
    				/* $product->setData('mv_product_id',$json_result['mvProduct']['ProductID']);
    				$product->save(); */
    			}
    		
    			return $json_result['mvProduct']['ProductID'];
    		}
    		else
    		{
    			$entityId = $json_result['entityID'];
    			if (!empty($entityId) && $entityId > 0){
    				if (strpos( $json_result['ResponseStatus']['Message'], 'and was since deleted') !== false) {
    					$result = array(
    							'mvProductId' => $json_result['entityID'],
    							'errorcode' => 'isdeleted'
    					);
    					return $result;
    				}
    				else
    				{
    					$this->updateProduct($productId,$entityId);
    					/* $product->setData('mv_product_id',$json_result['entityID']);
    					$product->save(); */
    					
    					$data['mvProduct']['ProductID'] = $entityId;
    					$data['mvRecordAction'] = 'Update';
    					$json_result = $this->_mvHelper->makeJsonRequest($data ,'ProductUpdate',$productId);
    				}
    			}
    		}
    	}
    	return 0;
    }
	
    public function deleteProduct($product)
    {
    	$productId = $product->getId();
    	$megaVentoryId = $product->getData('mv_product_id');
    
    	if(isset($megaVentoryId) && $megaVentoryId!=NULL)
    	{
    		
    		$data = array
    		(
    				'APIKEY' => $this->APIKEY,
    				'ProductIDToDelete'=> $megaVentoryId
    		);
    			
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'ProductDelete',$productId);
    
    		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    		if ($errorCode == '0'){//no errors
    			//make null the back end reference
    			$this->updateProduct($productId,'null');
    
    		}
    	}
    }
    
    public function addShippingProduct($megaventoryHelper)
    {
    	$shippingSKU = $this->_scopeConfig->getValue('megaventory/general/shippingproductsku');
    	if (empty($shippingSKU))
    		$shippingSKU = 'shipping_01';
    
    	$data = array
    	(
    			'APIKEY' => $this->APIKEY,
    			'mvProduct'=> array
    			(
    					'ProductID' => '0',
    					'ProductType' => 'BuyFromSupplier',
    					'ProductSKU' => $shippingSKU,
    					'ProductEAN' => '',
    					'ProductDescription' => 'Default Magento Shipping',
    					'ProductVersion' => '',
    					'ProductLongDescription' => '',
    					'ProductCategoryID' => '0',
    					'ProductUnitOfMeasurement'=>'Unit(s)',
    					'ProductSellingPrice'=> '0',
    					'ProductPurchasePrice'=> '0',
    					'ProductWeight'=>'0',
    					'ProductLength'=>'0',
    					'ProductBreadth'=>'0',
    					'ProductHeight'=>'0',
    					'ProductImageURL'=> '',
    					'ProductComments'=>'',
    					'ProductCustomField1'=>'',
    					'ProductCustomField2'=>'',
    					'ProductCustomField3'=>'',
    					'ProductMainSupplierID'=>'0',
    					'ProductMainSupplierPrice'=>'0',
    					'ProductMainSupplierSKU'=>'',
    					'ProductMainSupplierDescription'=>''
    			),
    			'mvRecordAction'=>'Insert'
    	);
    		
    	try{
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'ProductUpdate',0);
    	}
    	catch (\Exception $ex){
    		return 'There was a problem connecting to your Megaventory account. Please try again.';
    	}
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode != '0'){
    		if (empty($json_result['entityID']))
    			return $json_result['ResponseStatus']['Message'];
    			
    		return true;
    	}
    	else
    	{
    		return true;
    	}
    }
    
    public function addDiscountProduct()
    {
    	$discountSKU = $this->_scopeConfig->getValue('megaventory/general/discountproductsku');
    	if (empty($discountSKU))
    		$discountSKU = 'discount_01';
    
    	$data = array
    	(
    			'APIKEY' => $this->APIKEY,
    			'mvProduct'=> array
    			(
    					'ProductID' => '0',
    					'ProductType' => 'BuyFromSupplier',
    					'ProductSKU' => $discountSKU,
    					'ProductEAN' => '',
    					'ProductDescription' => 'Magento Discount',
    					'ProductVersion' => '',
    					'ProductLongDescription' => '',
    					'ProductCategoryID' => '0',
    					'ProductUnitOfMeasurement'=>'Unit(s)',
    					'ProductSellingPrice'=> '0',
    					'ProductPurchasePrice'=> '0',
    					'ProductWeight'=>'0',
    					'ProductLength'=>'0',
    					'ProductBreadth'=>'0',
    					'ProductHeight'=>'0',
    					'ProductImageURL'=> '',
    					'ProductComments'=>'',
    					'ProductCustomField1'=>'',
    					'ProductCustomField2'=>'',
    					'ProductCustomField3'=>'',
    					'ProductMainSupplierID'=>'0',
    					'ProductMainSupplierPrice'=>'0',
    					'ProductMainSupplierSKU'=>'',
    					'ProductMainSupplierDescription'=>''
    			),
    			'mvRecordAction'=>'Insert'
    	);
    		
    	try{
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'ProductUpdate',0);
    	}
    	catch (\Exception $ex){
    		return 'There was a problem connecting to your Megaventory account. Please try again.';
    	}
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode != '0'){
    		if (empty($json_result['entityID']))
    			return $json_result['ResponseStatus']['Message'];
    			
    		return true;
    	}
    	else
    	{
    		return true;
    	}
    }
    
    public function importProductsToMegaventory($page = 1,$imported = 0){
    
    	
    	$simple_products = $this->_productCollectionFactory->create()
    	->addAttributeToSelect('name')
    	->addAttributeToSelect('description')
    	->addAttributeToSelect('price')
    	->addAttributeToSelect('cost')
    	->addFieldToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    	->addAttributeToFilter(
    			array(
    					array('attribute' => 'type_id', 'eq' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE),
    					array('attribute' => 'type_id', 'eq' => \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL)
    			))
    			->addAttributeToSort('type_id','ASC');
    
    
    	$simple_products->setPageSize(20);
    	$simple_products->setCurPage($page);
    	$totalCollectionSize = $simple_products->getSize();
    	$isLastPage = false;
    	if ((int)($totalCollectionSize/20) == $page-1)
    		$isLastPage = true;
    
    	$total = $imported;// + ($page-1)*10;
    	foreach($simple_products as $product) {
    		try{
    			$inserted = $this->insertSingleProduct($product);
    			if ($inserted == 0 || $inserted == 1)
    			{
    				$total++;
    				$message = $total.'/'.$totalCollectionSize;
    				$this->_mvHelper->sendProgress(31, $message, $page, 'products', false);
    			}
    		}
    		catch(\Exception $ex){
    			$this->_logger->info($ex->getMessage());
    			$event = array(
    					'code' => 'Product Insert',
    					'result' => '',
    					'magento_id' => $product->getId(),
    					'return_entity' => '0',
    					'details' => $ex->getMessage(),
    					'data' => ''
    			);
    			$this->_mvHelper->log($event);
    			continue;
    		}
    	}
    	if ($isLastPage){
    		$message = $total.'/'.$totalCollectionSize.' products imported'.$this->_registry->registry('tickImage');
    		if ($total != $totalCollectionSize){
    			$logUrl = $this->_backendUrl->getUrl("megaventory/index/log");
    			$message .= '<br>'.$totalCollectionSize-$total.' product(s) were not imported. Check <a href="'.$logUrl.'" target="_blank">Megaventory Log</a> for details'.$this->_registry->registry('errorImage');
    		}
    		$this->_mvHelper->sendProgress(31, $message, $page, 'products', true);
    		return false;
    	}
    	else{
    		$result =
    		array(
    				'nextpage' => $page+1,
    				'imported' => $total
    		);
    
    		return $result;
    	}
    
    }
    
    public function insertSingleProduct($product)
    {
    	$productId = $product->getEntityId();
    	$product = $this->_productLoader->create()->load($productId);
    
    	$megaVentoryId = $product->getData('mv_product_id');
    	$name = $product['name'];
    	$sku = $product['sku'];
    	if (isset($product['weight']) == false)
    		$weight = '0';
    	else
    		$weight = $product['weight'];
    		
    	if (isset($product['price']) == false)
    		$price = '0';
    	else
    		$price = $product['price'];
    	
    	$finalPrice = $product->getPriceInfo()->getPrice('final_price')->getValue();
    		
    	$taxAdjustment = $product->getPriceInfo()->getAdjustment('tax');
    		
    	if ($taxAdjustment->isIncludedInBasePrice()) {
    		$adjust = $taxAdjustment->extractAdjustment($finalPrice, $product);
    			
    		$finalPriceNoTax = round($finalPrice - $adjust,2);
    	}
    	else 
    		$finalPriceNoTax = $finalPrice;
    
    		
    	if (isset($product['cost']) == false)
    		$cost = '0';
    	else
    		$cost = $product['cost'];
    		
    	//pass supplier on the fly    		
    	$supplierAttributeCode =  $this->_scopeConfig->getValue('megaventory/general/supplierattributecode');

    	$mvSupplierId = '';
    	if (isset($supplierAttributeCode)){
    		$attribute = $this->_attributeFactory->create()->loadByCode('catalog_product', $supplierAttributeCode);
    		 
    		$frontendInput = $attribute->getFrontendInput();
    		
    		$magentoSupplierId = $product->getData($supplierAttributeCode);
    		
    		if ($frontendInput == 'text')
    			$supplierName = $magentoSupplierId;
    		else if ($frontendInput == 'select')
    			$supplierName = $product->getAttributeText($supplierAttributeCode);
    		
    		if (isset($magentoSupplierId) && ($frontendInput == 'text' || $frontendInput == 'select')){
    				
    			$this->logger->info('supplier name = '.$supplierName);
    		
    			if ($supplierName){
    		
    				$supplierData = array
    				(
    						'APIKEY' => $this->APIKEY,
    						'query'=> 'mv.SupplierClientName = "'.$supplierName.'"'
    				);
    					
    				$json_result = $this->_mvHelper->makeJsonRequest($supplierData ,'SupplierClientGet');
    		
    				$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    				if ($errorCode == '0'){//no errors
    						
    					//supplier exists
    					if (count($json_result['mvSupplierClients']) > 0){
    						$mvSupplierId = $json_result['mvSupplierClients'][0]['SupplierClientID'];
    					}
    					else //supplier is new
    					{
    						$supplierData = array
    						(
    								'APIKEY' => $this->APIKEY,
    								'mvSupplierClient' => array (
    										'SupplierClientID' => 0,
    										'SupplierClientType' => '1',
    										'SupplierClientName' => $supplierName,
    										'SupplierClientBillingAddress' => '',
    										'SupplierClientShippingAddress1' => '',
    										'SupplierClientShippingAddress2' => '',
    										'SupplierClientPhone1' => '',
    										'SupplierClientPhone2' => '',
    										'SupplierClientFax' => '',
    										'SupplierClientIM' => '',
    										'SupplierClientEmail' => '',
    										'SupplierClientTaxID' => '',
    										'SupplierClientComments' => ''
    								),
    								'mvRecordAction' => 'Insert'
    						);
    		
    						$json_result = $this->_mvHelper->makeJsonRequest($supplierData, 'SupplierClientUpdate',0);
    		
    						$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    						if ($errorCode == '0'){//no errors
    							$mvSupplierId = $json_result['mvSupplierClient']['SupplierClientID'];
    						}
    					}
    				}
    					
    				$this->logger->info('mv supplier id = '.$mvSupplierId);
    			}
    		}
    	}
    
    	
    	$version = '';
    	$parentIds = $this->_catalogProductTypeConfigurable->getParentIdsByChild($productId);
    	
    	if (isset($parentIds) && isset($parentIds[0]))
    	{

    		$parentProduct = $this->_productLoader->create()->load($parentIds[0]);
    		if (isset($parentProduct)){
    			$simpleProduct = $product;
    			$product = $parentProduct;
    				
    			$productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
    			$attributeOptions = array();
    			$attributeValues = array();
    	
    			foreach ($productAttributeOptions as $productAttribute) {
    				//$attributeOptions[$productAttribute['attribute_code']] = $productAttribute['store_label'];
    	
    				foreach ($productAttribute['values'] as $attribute) {
    					if ($attribute['value_index'] == $simpleProduct[$productAttribute['attribute_code']])
    					{
    						$attributeValues[$productAttribute['store_label']] = $attribute['label'];
    						break;
    					}
    					//$attributeOptions[$productAttribute['label']][$attribute['value_index']] = $attribute['store_label'];
    				}
    			}
    			foreach ($attributeValues as $Key => $Value)
    				$version .= $Key . ':' . $Value . ';';
    		}
    	}
    	
    	
    	$shortDescription = '';
    	if (isset($product['short_description'])){
    		$shortDescription = $product['short_description'];
    		if (strlen($shortDescription) > 400)
    			$shortDescription = mb_substr($shortDescription,0,400, "utf-8");
    			//$shortDescription = substr($shortDescription, 0, 400);
    	}
    	$description = '';
    	if (isset($product['description']))
    	{
    		$description = $product['description'];
    		if (strlen($description) > 400)
    			$description = mb_substr($description,0,400, "utf-8");
    			//$description = substr($description, 0, 400);
    	}
    	
    	try{
    		$image = $this->_productHelper->getImageUrl($product);
    		//$this->_imagetHelper->init($product, 'product_base_image');
    	}
    	catch(\Exception $e)
    	{
    		$image = '';
    	}
    	
    	$mvProductId = '0';
    	$mvRecordAction = 'Insert';
    
    	$categoryIds = $product->getCategoryIds();
    	$mvCategoryId = '0';
    	if (is_array($categoryIds)) {
    		//randomly choose the first category
    		if (isset($categoryIds[0])){

    			$category = $this->_categoryLoader->create()->load($categoryIds[0]);
    			$categoryId = $category->getEntityId();
    				
    			$mvCategoryId = $category->getData('mv_productcategory_id');
    	
    			//if user adds a product that belongs to an unsynced category
    			//megaventory then insert it to megaventory as orphan
    			if (isset($mvCategoryId) == false || $mvCategoryId == NULL){
    				$mvCategoryId = '0';
    			}
    		}
    	}
    	

    	$attibuteSetId = $product->getAttributeSetId();
    	$attributeSetName = $this->_attributeSetFactory->create()->load($attibuteSetId)->getAttributeSetName();
    	
    	//prepare data
    	
    	$data = array
    	(
    			'APIKEY' => $this->APIKEY,
    			'mvProduct'=> array
    			(
    					'ProductID' => $mvProductId,
    					'ProductType' => "BuyFromSupplier",
    					'ProductSKU' => $sku,
    					'ProductEAN' => '', //$product['ean'],
    					'ProductDescription' => $name,
    					'ProductVersion' => $version, //$product['version'],
    					'ProductLongDescription' => $shortDescription,
    					'ProductCategoryID' => $mvCategoryId,
    					'ProductUnitOfMeasurement'=>'Unit(s)',
    					'ProductSellingPrice'=>$finalPriceNoTax,
    					'ProductPurchasePrice'=>$cost,
    					'ProductWeight'=>$weight,
    					'ProductLength'=>'0',
    					'ProductBreadth'=>'0',
    					'ProductHeight'=>'0',
    					'ProductImageURL'=>$image,
    					'ProductComments'=>'',
    					'ProductCustomField1'=>$attributeSetName,
    					'ProductCustomField2'=>'',
    					'ProductCustomField3'=>'',
    					'ProductMainSupplierID'=> $mvSupplierId,
    					'ProductMainSupplierPrice'=>'0',
    					'ProductMainSupplierSKU'=>'',
    					'ProductMainSupplierDescription'=>'',
    						
    			),
    			'mvRecordAction'=>$mvRecordAction
    	);
    		
    	$json_result = $this->_mvHelper->makeJsonRequest($data ,'ProductUpdate',$productId);
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode == '0'){//no errors
    		if (strcmp('Insert', $mvRecordAction) == 0){
    			$this->updateProduct($productId,$json_result['mvProduct']['ProductID']);
    			$mvProductId = $json_result['mvProduct']['ProductID'];
    
    			//update alert level
    			/* $stockItem = $product->getStock_item();
    			$quantity = '0';
    			$alertLevel = 0;
    
    			if (isset($stockItem)){
    				$useConfigNotify = $stockItem->getData('use_config_notify_stock_qty');
    				if ($useConfigNotify == '1'){
    					//get config value
    					$configValue = $this->_scopeConfig->getValue('cataloginventory/item_options/notify_stock_qty');
    					if (isset($configValue))
    						$alertLevel = $configValue;
    					else
    						$alertLevel = 0;
    				}
    				else{
    					$alertLevel = $stockItem->getData('notify_stock_qty');
    				}
    			}
    
    			$inventory = $this->_inventoriesLoader->create()->loadDefault();
    
    			$alertData = array
    			(
    					'APIKEY' => $this->APIKEY,
    					'mvProductStockAlertsAndSublocationsList'=> array
    					(
    							'productID' => $mvProductId,
    							'mvInventoryLocationStockAlertAndSublocations' => array(
    									'InventoryLocationID' => $inventory->getData('megaventory_id'),
    									'StockAlertLevel' => $alertLevel
    							)
    								
    					)
    			);
    
    			$this->_mvHelper->makeJsonRequest($alertData ,'InventoryLocationStockAlertAndSublocationsUpdate');
    
    			$productStock = $this->_productStocksLoader->create()
    			->loadInventoryProductstock($inventory->getId(), $product->getId());
    				
    			$productStock->setProduct_id($productId);
    			$productStock->setInventory_id($inventory->getId());
    			$productStock->setStockalarmqty($alertLevel);
    			$productStock->save(); */
    
    		}
    	}
    	else
    	{
    		$entityId = $json_result['entityID']; //if product exists just sync them
    		if (!empty($entityId) && $entityId > 0){
    			$this->updateProduct($productId,$entityId);
    			$mvProductId = $entityId;
    
    			//update alert level
    			$stockItem = $product->getStock_item();
    			$quantity = '0';
    			$alertLevel = 0;
    
    			if (isset($stockItem)){
    				$useConfigNotify = $stockItem->getData('use_config_notify_stock_qty');
    				if ($useConfigNotify == '1'){
    					//get config value
    					$configValue = $this->_scopeConfig->getValue('cataloginventory/item_options/notify_stock_qty');
    					if (isset($configValue))
    						$alertLevel = $configValue;
    					else
    						$alertLevel = 0;
    				}
    				else{
    					$alertLevel = $stockItem->getData('notify_stock_qty');
    				}
    			}
    
    			$inventory = $this->_inventoriesLoader->create()->loadDefault();
    
    			$alertData = array
    			(
    					'APIKEY' => $this->APIKEY,
    					'mvProductStockAlertsAndSublocationsList'=> array
    					(
    							'productID' => $mvProductId,
    							'mvInventoryLocationStockAlertAndSublocations' => array(
    									'InventoryLocationID' => $inventory->getData('megaventory_id'),
    									'StockAlertLevel' => $alertLevel
    							)
    								
    					)
    			);
    
    			$this->_mvHelper->makeJsonRequest($alertData ,'InventoryLocationStockAlertAndSublocationsUpdate');
    
    			$productStock = $this->_productStocksLoader->create()
    			->loadInventoryProductstock($inventory->getId(), $product->getId());
    				
    			$productStock->setProduct_id($productId);
    			$productStock->setInventory_id($inventory->getId());
    			$productStock->setStockalarmqty($alertLevel);
    			$productStock->save();
    
    
    			return 1;
    		}
    	}
    
    	return $errorCode;
    }
    
    public function getBundleOptions(\Magento\Quote\Model\Quote\Item $item)
    {
    	$options = array();
    	$product = $item->getProduct();
    
    	
		//\Magento\Bundle\Model\Product\Type    	
    	$typeInstance = $product->getTypeInstance(true);
    	
    	// get bundle options
    	$optionsQuoteItemOption = $item->getOptionByCode('bundle_option_ids');
    	$bundleOptionsIds = $optionsQuoteItemOption ? unserialize($optionsQuoteItemOption->getValue()) : array();
    	if ($bundleOptionsIds) {
    		/**
    		 * @var Mage_Bundle_Model_Mysql4_Option_Collection
    		 */
    		$optionsCollection = $typeInstance->getOptionsByIds($bundleOptionsIds, $product);
    
    		// get and add bundle selections collection
    		$selectionsQuoteItemOption = $item->getOptionByCode('bundle_selection_ids');
    
    		$bundleSelectionIds = unserialize($selectionsQuoteItemOption->getValue());
    
    		if (!empty($bundleSelectionIds)) {
    			$selectionsCollection = $typeInstance->getSelectionsByIds(
    					unserialize($selectionsQuoteItemOption->getValue()),
    					$product
    			);
    
    			$bundleOptions = $optionsCollection->appendSelections($selectionsCollection, true);
    			foreach ($bundleOptions as $bundleOption) {
    				if ($bundleOption->getSelections()) {
    
    					$bundleSelections = $bundleOption->getSelections();
    
    					$option = array();
    					foreach ($bundleSelections as $bundleSelection) {
    						$qty = $this->getSelectionQty($product, $bundleSelection->getSelectionId()) * 1;
    						
    						$price = $this->getSelectionFinalPrice($item, $bundleSelection);
    						
    						$option['qty'] = $qty;
    						$option['price'] = $price;
    						$option['product'] = $bundleSelection;
    							
    						$options[$bundleSelection->getProductId()] = $option;
    					}
    				}
    			}
    		}
    	}
    
    	return $options;
    }
    
    public function getSelectionQty($product, $selectionId)
    {
    	$selectionQty = $product->getCustomOption('selection_qty_' . $selectionId);
    	if ($selectionQty) {
    		return $selectionQty->getValue();
    	}
    	return 0;
    }
    
    public function getSelectionFinalPrice(\Magento\Quote\Model\Quote\Item $item,
    		\Magento\Catalog\Model\Product $selectionProduct)
    {
    	$selectionProduct->unsetData('final_price');
    	$product = $item->getProduct();
    	
    	$priceModel = $product->getPriceModel();
    	
    	return $priceModel->getSelectionFinalPrice(
    			$item->getProduct(),
    			$selectionProduct,
    			$item->getQty() * 1,
    			$this->getSelectionQty($item->getProduct(), $selectionProduct->getSelectionId()),
    			false,
    			true
    	);
    }
    
    public function addBundleProduct($product,$bundleCode,$options){
    	$productId = $product->getEntityId();
    	$product = $this->_productLoader->create()->load($productId);
    
    
    	$name = $product['name'];
    	//$sku = $product['sku'].'_'.$bundleCode;
    	//$sku = $bundleCode;
    	$sku = 'bom_'.$this->generateRandomString();
    
    	if (isset($product['weight']) == false)
    		$weight = '0';
    	else
    		$weight = $product['weight'];
    		
    	$price = '0';
    	if (!empty($product['price']))
    		$price = $product['price'];
    
    	$cost = '0';
    	if (!empty($product['cost']))
    		$cost = $product['cost'];
    		
    		
    	$version = '';
    
    		
    	$shortDescription = '';
    	if (isset($product['short_description'])){
    		$shortDescription = $product['short_description'];
    		if (strlen($shortDescription) > 400)
    			$shortDescription = mb_substr($shortDescription,0,400, "utf-8");
    			//$shortDescription = substr($shortDescription, 0, 400);
    	}
    	$description = '';
    	if (isset($product['description']))
    	{
    		$description = $product['description'];
    		if (strlen($description) > 400)
    			$description = mb_substr($description,0,400, "utf-8");
    			//$description = substr($description, 0, 400);
    	}
    
    
    	try{
    		$image = $this->_productHelper->getImageUrl($product);
    	}
    	catch(\Exception $e)
    	{
    		$image = '';
    	}
    
    	$mvProductId = '0';
    	$mvRecordAction = 'Insert';
    
    	$categoryIds = $product->getCategoryIds();
    	
    	$mvCategoryId = '0';
    	if (is_array($categoryIds)) {
    		//randomly choose the first category
    		if (isset($categoryIds[0])){
    			$category = $this->_categoryLoader->create()->load($categoryIds[0]);
    			$categoryId = $category->getEntityId();
    
    			$mvCategoryId = $category->getData('mv_productcategory_id');
    
    			//if user adds a product that belongs to an unsynced category
    			//megaventory then insert it to megaventory as orphan
    			if (isset($mvCategoryId) == false || $mvCategoryId == NULL){
    				$mvCategoryId = '0';
    			}
    		}
    	}
    
    	$this->_logger->info('before bundle insert ');

    	$attributeSetName = $this->_attributeSetFactory->create()->load($attibuteSetId)->getAttributeSetName();
    	
    	$productType = 'product type:'.$product->getType_id();
    
    	//prepare data
    	$data = array
    	(
    			'APIKEY' => $this->APIKEY,
    			'mvProduct'=> array
    			(
    					'ProductID' => $mvProductId,
    					'ProductType' => "ManufactureFromWorkOrder",
    					'ProductSKU' => $sku,
    					'ProductEAN' => '', //$product['ean'],
    					'ProductDescription' => $name,
    					'ProductVersion' => $version, //$product['version'],
    					'ProductLongDescription' => $shortDescription,
    					'ProductCategoryID' => $mvCategoryId,
    					'ProductUnitOfMeasurement'=>'Unit(s)',
    					'ProductSellingPrice'=>$price,
    					'ProductPurchasePrice'=>$cost,
    					'ProductWeight'=>$weight,
    					'ProductLength'=>'0',
    					'ProductBreadth'=>'0',
    					'ProductHeight'=>'0',
    					'ProductImageURL'=>$image,
    					'ProductComments'=>'bundle product',
    					'ProductCustomField1'=>$attributeSetName,
    					'ProductCustomField2'=>'',
    					'ProductCustomField3'=>'',
    					'ProductMainSupplierID'=>'0',
    					'ProductMainSupplierPrice'=>'0',
    					'ProductMainSupplierSKU'=>'',
    					'ProductMainSupplierDescription'=>'',
    						
    			),
    			'mvRecordAction'=>$mvRecordAction
    	);
    		
    	$json_result = $this->_mvHelper->makeJsonRequest($data ,'ProductUpdate',$productId);
    
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode == '0'){//no errors
    			
    		$mvRawMaterials = array();
    		foreach ($options as $option){
    
    			$rawProduct = $option['product'];
    				
    			$mvRawMaterialItem = array(
    					'ProductSKU' => $rawProduct->getSku(),
    					'RawMaterialQuantity' => $option['qty']
    			);
    
    			$mvRawMaterials[] = $mvRawMaterialItem;
    		}
    			
    		$bomData = array();
    		//$data['mvProduct']['mvRawMaterials'] = $mvRawMaterials;
    			
    		unset($data['APIKEY']);
    		unset($data['mvRecordAction']);
    		$bomData['APIKEY'] = $this->APIKEY;
    		$bomData['mvRecordAction'] = 'Update';
    		$bomData['mvProductBOM']['ProductSKU'] = $sku;
    		$bomData['mvProductBOM']['mvRawMaterials'] = $mvRawMaterials;
    
    			
    		$json_result = $this->_mvHelper->makeJsonRequest($bomData ,'ProductBOMUpdate',$productId);
    			
    		$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    		if ($errorCode == '0'){//no errors
    			$this->updateMegaventoryBOMs($productId, $json_result['mvProductBOM']['ProductID'], $bundleCode, $sku);
    
    			//add newly created bom also as simple product in magento
    			$this->addBomAsSimpleProduct($product,$sku,$bundleCode,$json_result['mvProductBOM']['ProductID']);
    
    			return $sku;
    		}
    	}
    	return -1;
    }
    
    public function addBomAsSimpleProduct($parentProduct, $sku, $bundleCode,$megaventoryId){
    	$product = $this->_productLoader->create();
    	// Build the product
    	$product->setSku($sku);
    	$product->setAttributeSetId($parentProduct->getAttributeSetId());
    	$product->setTypeId('simple');
    	$product->setName($parentProduct->getName()." -- ".$bundleCode);
    	$product->setCategoryIds($parentProduct->getCategoryIds());
    	$product->setWebsiteIDs($parentProduct->getWebsiteIds());
    	$product->setDescription('Automatically created BOM product');
    	$product->setShortDescription('Automatically created BOM product');
    	$product->setPrice(0); # Set some price
    	# Custom created and assigned attributes
    	$product->setHeight('0');
    	$product->setWidth('0');
    	$product->setDepth('0');
    	//Default Magento attribute
    	$product->setWeight(0);
    	$product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
    	$product->setStatus(1);//enabled
    	$product->setTaxClassId(1);
    	$product->setStockData(array(
    			'is_in_stock' => 1,
    			'qty' => 1
    	));
    	$product->setCreatedAt(strtotime('now'));
    
    	try {
    		$product = $product->save();
    			
    		//add megaventory product id
    		$this->updateProduct($product->getId(), $megaventoryId);
    			
    		//add initial megaventory stock
    		$inventories = $this->_inventoriesHelper->getInventories();
    		$stockData = array('stockqty'=>0,'stockqtyonhold'=>0,'stockalarmqty'=>0,'stocknonshippedqty'=>0,
    				'stocknonreceivedqty'=>0,'stockwipcomponentqty'=>0,'stocknonreceivedwoqty'=>0,'stocknonallocatedwoqty'=>0);
    		foreach ($inventories as $inventory){
    			$this->_inventoriesHelper->updateInventoryProductStock($product->getId(),$inventory->getId(),$stockData);
    		}
    	}
    	catch (\Exception $ex) {
    		//Handle the error
    	}
    }
    
    public function undeleteProduct($mvProductId){
    	$data = array(
    			'APIKEY' => $this->APIKEY,
    			'ProductIDToUndelete' => $mvProductId
    	);
       
    	$this->_mvHelper->makeJsonRequest($data, 'ProductUndelete');
    }
    
    public function bundleProductExists($bundleCode){
    	//check local bom table
    	return $this->_bomLoader->create()->loadByBOMCode($bundleCode);    
    }
    
    public function exportStock($inventoryName,\Magento\Framework\App\Filesystem\DirectoryList $directoryList){
    	$simple_products = $this->_productCollectionFactory->create()
    	//->setPage(1, 10)
    	->addAttributeToSelect('name')
    	->addAttributeToSelect('description')
    	->addAttributeToSelect('cost')
    	->addAttributeToSelect('qty')
    	->addFieldToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    	->addAttributeToFilter('type_id', \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    	->joinField(
    			'qty',
    			'cataloginventory_stock_item',
    			'qty',
    			'product_id=entity_id',
    			'{{table}}.stock_id=1',
    			'left'
    	);
    
    	/* $ioConfig = array(
    			'path' => 'var/export'
    	);
    	$file = new File();
    	$file->setAllowCreateFolders(true);
    	$file->open($ioConfig);
    	$file->open('InitialQuantities.csv', 'w+'); */
    	
    	$store = $this->_storeManager->getStore();
    	$baseCurrencyCode = $store->getBaseCurrencyCode();
    	
    	$heading = array(
    			'0' => 'Product Category',
    			'1'	=> 'Product Description',
    			'2' => 'SKU',
    			'3'	=> 'EAN',
    			'4' => 'Unit of Stock',
    			'5' => 'Quantity - '.$inventoryName,
    			'6' => 'Unit Cost (average cost) ('.$baseCurrencyCode.')',
    			'7' => 'Remarks',
    	);
    	
    	$outputFileName = "InitialQuantities.csv";
    	$outputFile = $directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA). DIRECTORY_SEPARATOR . $outputFileName;
    	
    	$handle = fopen($outputFile, 'w');
    	fputcsv($handle, $heading);
    	    	   	
    	foreach($simple_products as $product) {
    		try{
    			//ignore products which are not synchronized
    			if (empty($product['mv_product_id']))
    				continue;
    
    			$sku = trim($product['sku']);
    			if (isset($product['qty']) && $product['qty'] > 0)
    				$qty = $product['qty'];
    			else
    				continue;
    
    			$row  = array
    			(
    					'0' => '',
    					'1'	=> '',
    					'2' => $sku,
    					'3'	=> '',
    					'4' => '',
    					'5' => $qty,
    					'6' => isset($product['cost']) ? $product['cost'] : '0',
    					'7' => '',
    			);

    			fputcsv($handle, $row);
    		}
    		catch(\Exception $ex){
    		}
    	}

    
    	//add very large initial quantities for virtual products
    	$virtual_products = $this->_productCollectionFactory->create()
    	->addAttributeToSelect('name')
    	->addAttributeToSelect('description')
    	->addAttributeToSelect('cost')
    	->addAttributeToSelect('qty')
    	->addFieldToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    	->addAttributeToFilter('type_id', \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL);
    
    	foreach($virtual_products as $product) {
    		try{
    			//$row = '"","","'.$product['sku'].'","","","'.$product['qty'].'","'.$product['cost'].'",""';
    			$sku = trim($product['sku']);
    			if (isset($product['qty']) && $product['qty'] > 0)
    				$qty = $product['qty'];
    			else
    				$qty = '0';
    			$row  = array
    			(
    					'0' => '',
    					'1'	=> '',
    					'2' => $sku,
    					'3'	=> '',
    					'4' => '',
    					'5' => '1000000',
    					'6' => isset($product['cost']) ? $product['cost'] : '0',
    					'7' => '',
    			);
    			
    			fputcsv($handle, $row);
    		}
    		catch(\Exception $ex){
    			$this->_logger->info($ex->getMessage());
    		}
    	}
    
    	$shippingSKU = $this->_scopeConfig->getValue('megaventory/general/shippingproductsku');
    	$discountSKU = $this->_scopeConfig->getValue('megaventory/general/discountproductsku');
    	
    
    	$row  = array
    	(
    			'0' => '',
    			'1'	=> '',
    			'2' => $shippingSKU,
    			'3'	=> '',
    			'4' => '',
    			'5' => '1000000',
    			'6' => '0',
    			'7' => '',
    	);
    	fputcsv($handle, $row);
    
    	$row  = array
    	(
    			'0' => '',
    			'1'	=> '',
    			'2' => $discountSKU,
    			'3'	=> '',
    			'4' => '',
    			'5' => '1000000',
    			'6' => '0',
    			'7' => '',
    	);
    	fputcsv($handle, $row);
    
    	fclose($handle);
    	
    	return $outputFile;
    }
    
    private function updateProduct($productId, $mvProductId){
    	$connection = $this->_resource->getConnection();
    	$table = $this->_resource->getTableName('catalog_product_entity' );
    	$sql_insert = "update ".$table." set mv_product_id = ".$mvProductId." where entity_id = ".$productId;
    	$connection->query($sql_insert);
    }
    
    public function generateRandomString($length = 10) {
    	return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }
    
    public function updateMegaventoryBOMs($magentoId, $megaventoryId, $autoCode,$megaventorySKU){
    	$connection = $this->_resource->getConnection();
    	$tableName = $this->_resource->getTableName('megaventory_bom');
    	
    	$sql_insert = "insert into ".$tableName." (magento_product_id,megaventory_id,auto_code,megaventory_sku) values (".$magentoId.",".$megaventoryId.",'".$autoCode."','".$megaventorySKU."')";
    	$connection->query($sql_insert);
    }
}
