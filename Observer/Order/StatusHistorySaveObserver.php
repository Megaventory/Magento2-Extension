<?php

namespace Mv\Megaventory\Observer\Order;

use \Magento\Framework\Event\ObserverInterface;

class StatusHistorySaveObserver implements ObserverInterface {
	protected $_scopeConfig;
	private $_orderHelper;
	private $_registry;
	private $_messageManager;
	protected $_logger;
	
	public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, 
			\Mv\Megaventory\Helper\Order $orderHelper, 
    		\Magento\Framework\Registry $registry, 
			\Magento\Framework\Message\ManagerInterface $messageManager, 
			\Psr\Log\LoggerInterface $logger) // log injection
{
		$this->_scopeConfig = $scopeConfig;
		$this->_orderHelper = $orderHelper;
		$this->_registry = $registry;
		
		$this->_messageManager = $messageManager;
		$this->_logger = $logger;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer) {
		$orderSynchronization = $this->_scopeConfig->getValue ( 'megaventory/general/ordersynchronization' );
		if (empty ( $orderSynchronization ) || $orderSynchronization === '0')
			return;
		
		$statusHistory = $observer->getStatus_history();
		
		$comment = $statusHistory->getComment ();
		
		if (! empty ( $comment )) {
			$registryComment = $this->_registry->registry( 'mvcustomercomment' );
			$this->_registry->unregister ( 'mvcustomercomment' );
			$registryComment .= $comment;
			$this->_registry->register ( 'mvcustomercomment', $registryComment );
		}
	}
}