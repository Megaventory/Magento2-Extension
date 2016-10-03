<?php

namespace Mv\Megaventory\Observer\Category;

use \Magento\Framework\Event\ObserverInterface;

class DeleteObserver implements ObserverInterface {
	private $_mvCategoryHelper;
	protected $_logger;
	
	public function __construct(
			\Mv\Megaventory\Helper\Category $mvCategoryHelper, 
			\Psr\Log\LoggerInterface $logger) 
	{
		$this->_mvCategoryHelper = $mvCategoryHelper;
		
		$this->_logger = $logger;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer) {
		
		$event = $observer->getEvent ();
		$category = $event->getCategory ();
		
		if ($category != null)
			$this->_mvCategoryHelper->deleteCategoriesRecursively($category);
		
	}
}