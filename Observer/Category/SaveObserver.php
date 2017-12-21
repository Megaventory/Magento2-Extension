<?php

namespace Mv\Megaventory\Observer\Category;

use \Magento\Framework\Event\ObserverInterface;

class SaveObserver implements ObserverInterface {
	private $_mvCategoryHelper;
	protected $_commonHelper;
	protected $_logger;
	
	public function __construct(\Mv\Megaventory\Helper\Category $mvCategoryHelper,  
			\Mv\Megaventory\Helper\Common $commonHelper,
			\Psr\Log\LoggerInterface $logger) 
	{
		$this->_mvCategoryHelper = $mvCategoryHelper;
		$this->_commonHelper = $commonHelper;
		
		$this->_logger = $logger;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer) {
		if (! $this->_commonHelper->isMegaventoryEnabled())
			return;
		
		$event = $observer->getEvent ();
		$category = $event->getCategory ();
		
		if ($category != null)
			$this->_mvCategoryHelper->addCategory($category);
		
	}
}