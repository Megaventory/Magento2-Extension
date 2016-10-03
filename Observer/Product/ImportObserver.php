<?php 
namespace Mv\Megaventory\Observer\Product;

use \Magento\Framework\Event\ObserverInterface;

class ImportObserver implements ObserverInterface
{
	private $_mvProductHelper;
	private $_messageManager;
    private $_productLoader;
	
    protected $_logger;
    
  public function __construct(
    	\Mv\Megaventory\Helper\Product $mvProductHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Catalog\Model\ProductFactory $productLoader,
  		\Psr\Log\LoggerInterface $logger //log injection
  		)
  {
  	$this->_mvProductHelper = $mvProductHelper;
  	$this->_messageManager = $messageManager;
  	$this->_productLoader = $productLoader;
  	
    $this->_logger = $logger;
  }

  public function execute(\Magento\Framework\Event\Observer $observer)
  {
  	$event = $observer->getEvent();
  	$adapter = $observer->getAdapter();
  	
  	if ($adapter){
  		$newSku = $adapter->getNewSku();
  			
  		foreach ($newSku as $sku => $skuValues) {
  	
  			$productId = $skuValues['entity_id'];
  			$this->_productLoader->create()->load($productId);
  				
  			if ($product->getId()){
  				$sku = $product->getSku();
  				$megaventoryId = $product->getData('mv_product_id');
  				$startsWith = $this->startsWith($sku, 'bom_');
  				if ($startsWith && empty($megaventoryId)) //it is an insert of a bom and we should ignore
  					return;
  	
  				$this->_mvProductHelper->addProduct($product);
  			}
  		}
  	}
  }
}