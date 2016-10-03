<?php 
namespace Mv\Megaventory\Observer\Order;

use \Magento\Framework\Event\ObserverInterface;

class SubmitSuccessObserver implements ObserverInterface
{
	private $_orderHelper;
	private $_messageManager;
	
    protected $_logger;
    
  public function __construct(
    	\Mv\Megaventory\Helper\Order $orderHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
  		\Psr\Log\LoggerInterface $logger //log injection
  		)
  {
  	$this->_orderHelper = $orderHelper;
  	
  	$this->_messageManager = $messageManager;
  	$this->_logger = $logger;
  }

  public function execute(\Magento\Framework\Event\Observer $observer)
  {
    //Observer execution code...
  	$order = $observer->getEvent()->getOrder();
  	$quote = $observer->getEvent()->getQuote();
  	
  	$this->_orderHelper->addOrder($order, $quote);
  	
  }
}