<?php

namespace Mv\Megaventory\Observer\Customer;

use \Magento\Framework\Event\ObserverInterface;

class DeleteObserver implements ObserverInterface {
	private $_mvCustomerHelper;
	protected $_logger;
	
	public function __construct(
			\Mv\Megaventory\Helper\Customer $mvCustomerHelper, 
			\Psr\Log\LoggerInterface $logger) 
	{
		$this->_mvCustomerHelper = $mvCustomerHelper;
		
		$this->_logger = $logger;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer) {
		
		$event = $observer->getEvent();
		$customer = $event->getCustomer();
	
		$this->_mvCustomerHelper->deleteCustomer($customer);
		
	}
}