<?php

namespace Mv\Megaventory\Helper;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Mv\Megaventory\Logger\Logger;
use \Mv\Megaventory\Model\LogFactory;

class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_scopeConfig;
	protected $_registry;
    private $_mvHelper;
    protected $_currenciesHelper;
    protected $_taxesHelper;
    protected $_customerHelper;
    protected $_productHelper;
    
    protected $_storeManager;
    protected $_orderLoader;
    protected $_productLoader;
    protected $_customerLoader;
    protected $_currenciesLoader;
    protected $_invnentoriesLoader;
    protected $_bomLoader;
    protected $_taxCalculation;
    protected $_taxConfig;
    protected $_magentoTaxHelper;
    protected $_addressRenderer;
    protected $_priceCurrency;
    
	protected $logger;
	protected $mvLogFactory;
    private $APIKEY;
	
	
	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		ScopeConfigInterface $scopeConfig,
    	\Magento\Framework\Registry $registry, 
		Data $mvHelper,
		\Mv\Megaventory\Helper\Customer $customerHelper,
		\Mv\Megaventory\Helper\Currencies $currenciesHelper,
		\Mv\Megaventory\Helper\Taxes $taxesHelper,
		\Mv\Megaventory\Helper\Product $productHelper,
		\Magento\Store\Model\StoreManager $storeManager,
        \Magento\Sales\Model\OrderFactory $orderLoader,
        \Magento\Catalog\Model\ProductFactory $productLoader,
        \Magento\Customer\Model\CustomerFactory $customerLoader,
        \Mv\Megaventory\Model\CurrenciesFactory $currenciesLoader,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
        \Mv\Megaventory\Model\BomFactory $bomLoader,
		\Magento\Tax\Model\Calculation $taxCalculation,
		\Magento\Tax\Model\Config $taxConfig,
		\Magento\Tax\Helper\Data $magentoTaxHelper,
		\Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
		\Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
		LogFactory $mvLogFactory,
		Logger $logger
    ) {
		$this->_scopeConfig = $scopeConfig;
		$this->_registry = $registry; 
		
        $this->_mvHelper = $mvHelper;
        $this->_currenciesHelper = $currenciesHelper;
        $this->_taxesHelper = $taxesHelper;
        $this->_customerHelper = $customerHelper;
        $this->_productHelper = $productHelper;
        
        $this->_storeManager = $storeManager;
        $this->_orderLoader = $orderLoader;
        $this->_productLoader = $productLoader;
        $this->_customerLoader = $customerLoader;
        $this->_currenciesLoader = $currenciesLoader;
        $this->_invnentoriesLoader = $inventoriesLoader;
        $this->_bomLoader = $bomLoader;
        $this->_taxCalculation = $taxCalculation;
        $this->_taxConfig = $taxConfig;
        $this->_magentoTaxHelper = $magentoTaxHelper;
        $this->_addressRenderer = $addressRenderer;
        $this->_priceCurrency = $priceCurrency;
        
        $this->mvLogFactory = $mvLogFactory;
		$this->logger = $logger;
		
		$this->APIKEY = $this->_scopeConfig->getValue('megaventory/general/apikey');
        parent::__construct($context);
    }
	
    public function addOrder(\Magento\Sales\Model\Order $order,\Magento\Quote\Model\Quote $quote)
    {
    	$increment_id = $order->getIncrementId();
    	$currency = $order->getOrderCurrency();
    	$orderCurrencyCode = $currency->getCurrency_code();
    	$store = $this->_storeManager->getStore();
    	$baseCurrencyCode = $store->getBaseCurrencyCode();
    
    	$tmpCurrency = $this->_currenciesLoader->create()->load($orderCurrencyCode,'code');
    	
    	if ($tmpCurrency->getId() == false){ //currency does not exist in local table
    		$this->_currenciesHelper->addSingleCurrency($orderCurrencyCode);
    	}
    
    
    	$billingAddress = $order->getBillingAddress();
    	//$billingAddressString = $billingAddress->format('oneline');
    	$billingAddressString = $this->_addressRenderer->format($billingAddress, 'oneline');
    	$billingEmail = $billingAddress->getEmail();
    	$billingTelephone = $billingAddress->getTelephone();
    	$billingAddressString = trim($billingAddressString);
    	if (!empty($billingTelephone))
    		$billingAddressString .= ','.$billingTelephone;
    
    	$shippingAddress = $order->getShippingAddress();
    	//$shippingAddressString = $shippingAddress->format('oneline');
    	$shippingAddressString = $this->_addressRenderer->format($shippingAddress, 'oneline');
    	$shippingEmail = $shippingAddress->getEmail();
    	$shippingTelephone = $shippingAddress->getTelephone();
    	$shippingAddressString = trim($shippingAddressString);
    	if (!empty($shippingTelephone))
    		$shippingAddressString .= ','.$shippingTelephone;
    
    
    	$orderDate = $order->getUpdated_at();
    	//$customer = $order->getCustomer();
    	$customer = $this->_customerLoader->create()->load($order->getCustomer_id());
    
    	$megaVentoryCustomerId = 0;
    
    	//if customer is guest
    	if ($order->getCustomer_group_id() == '0')
    	{
    		$defaultGuestId = $this->_scopeConfig->getValue('megaventory/general/defaultguestid');
    		if (isset($defaultGuestId))
    			$megaVentoryCustomerId = $defaultGuestId;
    	}
    	else
    	{
    		if (isset($customer)){
    			$megaVentoryCustomerId = $customer->getData('mv_supplierclient_id');
    
    			if(isset($megaVentoryCustomerId)==false || empty($megaVentoryCustomerId)) //customer not exists
    			{
    				$megaVentoryCustomerId = $this->_customerHelper->addCustomer($customer);
    			}
    		}
    	}
    
    	$history = $order->getAllStatusHistory();
    	$comments = '';
    	$customerComment = $this->_registry->registry('mvcustomercomment');
    	if (!empty($customerComment)){
    		$comments .= $customerComment.',';
    		$this->_registry->unregister('mvcustomercomment');
    	}
    
    	$items = $quote->getAllItems();
    	$salesOrderDetails = array();
    
    	$inventory = $this->_invnentoriesLoader->create()->loadDefault();
    
    	foreach ($items as $productItem) {
    		$product = $productItem->getProduct();
    		$product = $this->_productLoader->create()->load($product->getId());
    			
    		$productType = $product->getTypeId();
    		if ($productType == 'bundle'){
    			
    			$options = $this->_productHelper->getBundleOptions($productItem);
    
    			//keep bundle products to avoid duplicates
    			$bundles = array_fill_keys(array_keys($options),'1');
    			$bundleCode = '';
    			foreach ($options as $key => $value){
    				$bundleCode .= $value['qty'].'x'.$key.'_';
    			}
    			if (strlen($bundleCode) > 1)
    				$bundleCode = substr_replace($bundleCode ,"",-1);
    
    			$bundleMegaventory = $this->_productHelper->bundleProductExists($bundleCode);
    
    			$bundleSKU = '-1';
    
    			//add bundle product if not exists
    			if (!$bundleMegaventory->hasId()){
    				$this->_logger->info('add bundle product = '.$bundleCode);
    				$bundleSKU = $this->_productHelper->addBundleProduct($product,$bundleCode,$options);
    			}
    			else{
    				$this->_logger->info('bundle product exists = '.$bundleCode);
    				$bundleSKU = $bundleMegaventory->getMegaventory_sku();
    			}
    
    			$taxPercent = $productItem->getTax_percent();
    			if ($taxPercent == 0 && $productItem->getTax_amount()>0){
    				$taxPercent = round($productItem->getTax_amount()/$productItem->getRow_total()*100,2);
    			}
    			$this->_logger->info('item tax percent = '.$taxPercent);
    			$tax = $this->_taxesHelper->getTaxByPercentage($taxPercent);
    
    			if ($tax != false){
    				$salesOrderRowTaxID = $tax->getMegaventory_id();
    			}
    			else {
    				if ($taxPercent>0){
    					$megaventoryTaxId = $this->_taxesHelper->addMagentoTax($taxPercent);
    					if ($megaventoryTaxId != false)
    						$salesOrderRowTaxID = $megaventoryTaxId;
    				}
    				else
    					$salesOrderRowTaxID = '0';
    			}
    
    			//we need to set is_salable to option products
    			//because when we resynchronize an order we don't have
    			//a website id and products appear as no salable
    			if ($product->hasCustomOptions()) {
    				$customOption = $product->getCustomOption('bundle_option_ids');
    				$customOption = $product->getCustomOption('bundle_selection_ids');
    				$selectionIds = unserialize($customOption->getValue());
    				$selections = $product->getTypeInstance(true)->getSelectionsByIds($selectionIds, $product);
    				foreach ($selections->getItems() as $selection) {
    					$selection->setIsSalable(true);
    				}
    			}
    
    			$finalPriceNoTax = $this->getPrice($product, $product->getFinalPrice($product->getQty()), $taxPercent);
    
    			$this->_logger->info('final price no tax = '.$finalPriceNoTax);
    
    			//add order item
    			$salesOrderItem = array(
    					'SalesOrderRowQuantity' => $productItem->getQty(),
    					'SalesOrderRowUnitPriceWithoutTaxOrDiscount' => $finalPriceNoTax,
    					'SalesOrderRowTaxID' => $salesOrderRowTaxID,
    					'SalesOrderRowProductSKU' => $bundleSKU
    			);
    			$salesOrderDetails[] = $salesOrderItem;
    
    
    			//add work order
    			if ($bundleSKU != -1){
    				$woComments = 'product:'.$product->getName();
    				$woComments .= ',order:'.$increment_id;
    				$this->addWorkOrder($bundleSKU,$productItem->getQty(),$inventory->getData('megaventory_id'),$woComments, $product->getId());
    			}
    			//end of work order
    		}
    		else if ($productType == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE || $productType == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL)
    		{
    			//if product is checked through bundle then do nothing more
    			if (!empty($bundles)){
    				if (array_key_exists($product->getId(),$bundles)){
    					//though unset it not to ignore if purchased individually
    					unset($bundles[$product->getId()]);
    					continue;
    				}
    			}
    
    			$id = $product->getData('mv_product_id');
    			
    			if(!isset($id)){ //product does not exist
    				$megaventoryProductId = $this->_productHelper->addProduct($product);
    					
    				if (is_array($megaventoryProductId)){
    					$megaventoryProductId = $megaventoryProductId['mvProductId'];
    					$this->_productHelper->undeleteProduct($megaventoryProductId);
    				}
    			}
    			else
    				$megaventoryProductId = $id;
    
    
    			$parentItem = $productItem->getParentItem();
    			if (isset($parentItem)){
    				$productItem = $parentItem;
    				//$product = $productItem->getProduct();
    			}
    
    			$finalPriceNoTax = $this->getPrice($productItem->getProduct(), $productItem->getProduct()->getFinalPrice($productItem->getQty()));
    
    			$taxPercent = $productItem->getTax_percent();
    			$tax = $this->_taxesHelper->getTaxByPercentage($taxPercent);
    			$this->_logger->info('tax percent = '.$taxPercent);
    
    			if ($tax != false){
    				$salesOrderRowTaxID = $tax->getMegaventory_id();
    			}
    			else {
    				if ($taxPercent>0){
    					$megaventoryTaxId = $this->_taxesHelper->addMagentoTax($taxPercent);
    					if ($megaventoryTaxId != false)
    						$salesOrderRowTaxID = $megaventoryTaxId;
    				}
    				else
    					$salesOrderRowTaxID = '0';
    			}
    
    			$this->_logger->info('final price no tax = '.$finalPriceNoTax);
    
    			if ($megaventoryProductId != 0)
    			{
    				$salesOrderItem = array(
    						'SalesOrderRowQuantity' => $productItem->getQty(),
    						'SalesOrderRowUnitPriceWithoutTaxOrDiscount' => $finalPriceNoTax,
    						'SalesOrderRowTaxID' => $salesOrderRowTaxID,
    						'SalesOrderRowProductSKU' => $product->getSku()
    				);
    				$salesOrderDetails[] = $salesOrderItem;
    			}
    
    			//check if product is a bom and add a wo
    			if (strpos($product->getSku(),'bom_')!== false)
    			{
    				//check local bom table
    				$this->_bomLoader->create()->loadByBOMSku($product->getSku());
    				if ($bomProduct->hasId()){
    					$woComments = 'product:'.$product->getName();
    					$woComments .= ',order:'.$increment_id;
    					$this->addWorkOrder($product->getSku(), $productItem->getQty(), $inventory->getData('megaventory_id'), $woComments, $product->getId());
    				}
    			}
    			//end of bom check
    		}
    	}
    
    	//add shipping as product
    
    	//get base prices
    	/* $shippingNoTax = $order->getShipping_amount();
    		$shippingWithTax = $order->getShipping_incl_tax();
    		$shippingTax = $order->getShipping_tax_amount(); */
    	$shippingNoTax = $order->getBase_shipping_amount();
    	$shippingWithTax = $order->getBase_shipping_incl_tax();
    	$shippingTax = $order->getBase_shipping_tax_amount();
    
    
    	$shippingProductSKU = $this->_scopeConfig->getValue('megaventory/general/shippingproductsku');
    	$shippingOrderItem = array(
    			'SalesOrderRowQuantity' => '1',
    			'SalesOrderRowUnitPriceWithoutTaxOrDiscount' => $shippingNoTax,
    			'SalesOrderRowProductSKU' => $shippingProductSKU
    	);
        
    	$currentStore = $this->_storeManager->getStore();
    
    	$shippingRequest  = $this->_taxCalculation->getRateRequest(
    			$shippingAddress,
    			$billingAddress,
    			$customer->getTax_class_id(),
    			$currentStore
    	);
    	$shippingClassId   = $this->_magentoTaxHelper->getShippingTaxClass($order->getStore_id());
    	$shippingRequest->setProductClassId($shippingClassId);
    
    	$shippingTaxPercentage = $this->_taxCalculation->getRate($shippingRequest);
    
    	$this->_logger->info('shipping tax percentage = '.$shippingTaxPercentage);
    	//add shipping tax only if there is one
    	if ($shippingTaxPercentage > 0){
    		$shippingTaxEntity = $this->_taxesHelper->getTaxByPercentage($shippingTaxPercentage);
    			
    		if ($shippingTaxEntity != false){
    			$shippingTaxID = $shippingTaxEntity->getMegaventory_id();
    		}
    		else {
    			$megaventoryTaxId = $this->_taxesHelper->addMagentoTax($shippingTaxPercentage);
    			if ($megaventoryTaxId != false)
    				$shippingTaxID = $megaventoryTaxId;
    		}
    	
    		$shippingOrderItem['SalesOrderRowTaxID'] = $shippingTaxID;
    	}
    
    	if ($shippingNoTax > 0)
    		$salesOrderDetails[] = $shippingOrderItem;
    	//end of shipping
    
    	//discount handling
    	$totalDiscount = 0;
    	//discount amount is negative number
    	$discount = $order->getDiscount_amount();
    	if (!empty($discount) && $discount != 0){
    		$totalDiscount -= abs($discount);
    	}
    	$giftDiscount = $order->getData('gift_voucher_discount');
    	if (!empty($giftDiscount) && $giftDiscount != 0){
    		$totalDiscount -= abs($giftDiscount);
    	}
    
    	$discountProductSKU = $this->_scopeConfig->getValue('megaventory/general/discountproductsku');
    	if ($totalDiscount != 0)
    	{
    		$discountOrderItem = array(
    				'SalesOrderRowQuantity' => '1',
    				'SalesOrderRowUnitPriceWithoutTaxOrDiscount' => $totalDiscount,
    				'SalesOrderRowProductSKU' => $discountProductSKU
    		);
    		$salesOrderDetails[] = $discountOrderItem;
    	}
    	//end of discount
    
    	$subTotal = $order->getSubtotal();
    	$taxAmount = $order->getTax_amount();
    	$grandTotal = $order->getGrand_total();
    
    	$totalQty = $order->getTotal_qty_ordered();
    	$totalItemCount = $order->getTotal_item_count();
    	$status = $order->getStatus();
    	$shippingDescription = $order->getShipping_description();
    	$shippingMethod = $order->getShipping_method();
    	$paymentMethodTitle = $order->getPayment()->getMethodInstance()->getTitle();
    
    
    	$storeName = $order->getStore_name();
    	$comments .= 'ship:'.$shippingDescription.',pay:'.$paymentMethodTitle;
    
    	$tags = '';
    
    
    			$magentoInstallationId = $this->_scopeConfig->getValue('megaventory/general/magentoid');
    			if (!isset($magentoInstallationId))
    				$magentoInstallationId = "MagentoCommunity";
    
    			$data = array (
    					'APIKEY' => $this->APIKEY,
    					'mvSalesOrder' =>
    					array (
    							'SalesOrderNo' => $increment_id,
    							'SalesOrderReferenceNo' => $increment_id,
    							'SalesOrderReferenceApplication' => $magentoInstallationId,//magento, magento-2 ...
    							//always insert orders in base currency
    							'SalesOrderCurrencyCode' => $baseCurrencyCode,
    							//'SalesOrderCurrencyCode' => $orderCurrencyCode,
    							'SalesOrderClientID' => $megaVentoryCustomerId,
    							'SalesOrderBillingAddress' => $billingAddressString,
    							'SalesOrderShippingAddress' => $shippingAddressString,
    							'SalesOrderContactPerson' => $billingAddress->getLastname().' '.$billingAddress->getFirstname(),
    							'SalesOrderInventoryLocationID' => $inventory->getData('megaventory_id'),
    							'SalesOrderComments' => $comments,
    							'SalesOrderTags' => $tags,
    							'SalesOrderAmountShipping' => $shippingWithTax,
    							'SalesOrderDetails' => $salesOrderDetails,
    							'SalesOrderStatus' => 'Verified'
    					),
    					'mvRecordAction' => "Insert" );
    
    			$orderAdded = $this->_orderLoader->create()->loadByIncrementId($increment_id);
    			$json_result = $this->_mvHelper->makeJsonRequest($data ,'SalesOrderUpdate',$orderAdded->getId());
    
    
    			$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    			if ($errorCode == '0'){//no errors
    				$orderAdded->setData('mv_salesorder_id',$json_result['mvSalesOrder']['SalesOrderNo']);
    				$orderAdded->setData('mv_inventory_id',$inventory->getData('id'));
    				$orderAdded->save();
    			}
    }
    
    public function addWorkOrder($sku, $quantity, $mvInventoryId, $comments, $magentoId){
    	$data = array (
    			'APIKEY' => $this->APIKEY,
    			'mvWorkOrder' =>
    			array (
    					'WorkOrderId' => 0,
    					'WorkOrderNo' => 0,
    					'WorkOrderFinishedGoodSKU' => $sku,
    					'WorkOrderPriority' => 50,
    					'WorkOrderInventoryLocationID' => $mvInventoryId,
    					'WorkOrderComments' => $comments,
    					'WorkOrderTags' => '',
    					'WorkOrderOrderedQuantity' => $quantity,
    					'WorkOrderStatus' => 'Pending'
    			),
    			'mvRecordAction' => "Insert" );
    
    	$json_result = $this->_mvHelper->makeJsonRequest($data ,'WorkOrderUpdate',$magentoId);
    }
    
    public function getPrice($product, $price, $taxPercent=null, $shippingAddress = null, $billingAddress = null,
    		$ctc = null, $store = null, $priceIncludesTax = null
    ) {
    	if (!$price) {
    		return $price;
    	}
    	$store = $this->_storeManager->getStore();
    	
    	if (!$this->needPriceConversion($store)) {
    		//return $store->roundPrice($price);
    		return $this->_priceCurrency->round($price);
    	}
    	
    	if (is_null($priceIncludesTax)) {
    		$priceIncludesTax = $this->priceIncludesTax($store);
    	}
    
    	if ($taxPercent!=null)
    		$percent = $taxPercent;
    	else
    		$percent = $product->getTaxPercent();
    
    	$includingPercent = null;
    
    	$taxClassId = $product->getTaxClassId();
    	$this->_logger->info('tax class id = '.$taxClassId);
    	if (is_null($percent)) {
    		if ($taxClassId) {
    			$request = $this->_taxCalculation->getRateRequest($shippingAddress, $billingAddress, $ctc, $store);
    			$request->setProductClassId($taxClassId);
    			$percent = $this->_taxCalculation->getRate($request); 
    		}
    	}
    	if (!empty($taxClassId) && $taxClassId>0){
    		if ($priceIncludesTax) {
    			$request = $this->_taxCalculation->getRateRequest(false, false, false, $store);
    			$request->setProductClassId($taxClassId);
    			$includingPercent = $this->_taxCalculation->getRate($request); 
    		}
    	}
    	else
    	{
    		if ($percent && $priceIncludesTax)
    			$includingPercent = $percent;
    	}
    
    	$this->_logger->info('including percent  = '.$includingPercent);
    	if ($percent === false || is_null($percent)) {
    		if ($priceIncludesTax && !$includingPercent) {
    			return $price;
    		}
    	}
    
    	$product->setTaxPercent($percent);
    
    
    	if ($includingPercent != $percent) {
    		$price = $this->_calculatePrice ( $price, $includingPercent, false );
    	} else {
    		$price = $this->_calculatePrice ( $price, $includingPercent, false, true );
    	}
    		
    	//return $store->roundPrice($price);
    	return $this->_priceCurrency->round($price);
    }
    
    protected function _calculatePrice($price, $percent, $type, $roundTaxFirst = false)
    {
    	if ($type) {
    		$taxAmount = $this->_taxCalculation->calcTaxAmount($price, $percent, false, $roundTaxFirst);
    		return $price + $taxAmount;
    	} else {
    		$taxAmount = $this->_taxCalculation->calcTaxAmount($price, $percent, true, $roundTaxFirst);
    		return $price - $taxAmount;
    	}
    }
    
    public function needPriceConversion($store = null)
    {
    	$res = false;
    	
    	$priceDisplayType = $this->_taxConfig->getPriceDisplayType($store);
    	if ($this->priceIncludesTax($store)) {
    		switch ($priceDisplayType) {
    			case \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX:
    			case \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH:
    				return \Magento\Tax\Model\Config::PRICE_CONVERSION_MINUS;
    			case \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX:
    				$res = true;
    		}
    	} else {
    		switch ($priceDisplayType) {
    			case \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX:
    			case \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH:
    				return \Magento\Tax\Model\Config::PRICE_CONVERSION_PLUS;
    			case \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX:
    				$res = false;
    		}
    	}
    
    	if ($res === false) {
    		$res = $this->_taxConfig->displayCartPricesBoth();
    	}
    	return $res;
    }
    
    public function priceIncludesTax($store = null)
    {
    	return $this->_taxConfig->priceIncludesTax($store) || $this->_taxConfig->getNeedUseShippingExcludeTax();
    }
    
    public function cancelOrder($order)
    {
    	$mvOrderId = $order->getData('mv_salesorder_id');
    	if (!empty($mvOrderId)){
    		$data = array (
    				'APIKEY' => $this->APIKEY,
    				'mvSalesOrderNoToCancel' => $mvOrderId
    		);
    			
    		$json_result = $this->_mvHelper->makeJsonRequest($data ,'SalesOrderCancel',$order->getId());
    	}
    }
}
