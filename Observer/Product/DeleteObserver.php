<?php 
namespace Mv\Megaventory\Observer\Product;

use \Magento\Framework\Event\ObserverInterface;

class DeleteObserver implements ObserverInterface
{
	private $_mvProductHelper;
	
    protected $_logger;
    
  public function __construct(
    	\Mv\Megaventory\Helper\Product $mvProductHelper,
  		\Psr\Log\LoggerInterface $logger //log injection
  		)
  {
  	$this->_mvProductHelper = $mvProductHelper;
  	
    $this->_logger = $logger;
  }

  public function execute(\Magento\Framework\Event\Observer $observer)
  {
  	if ($observer->getEvent()->getProduct() != null)
  		$result = $this->_mvProductHelper->deleteProduct($observer->getEvent()->getProduct());
  }
}