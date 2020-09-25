<?php
namespace Mv\Megaventory\Observer\Order;

use \Magento\Framework\Event\ObserverInterface;

class SubmitSuccessObserver implements ObserverInterface
{
    private $_orderHelper;
    private $_messageManager;
    
    protected $_logger;
    protected $_scopeConfig;
    
    public function __construct(
        \Mv\Megaventory\Helper\Order $orderHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger //log injection
    ) {
        $this->_orderHelper = $orderHelper;
        $this->_scopeConfig = $scopeConfig;
    
        $this->_messageManager = $messageManager;
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
      //Observer execution code...
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        $orderSynchronization = $this->_scopeConfig->getValue('megaventory/general/ordersynchronization');
        if (empty($orderSynchronization) || $orderSynchronization === '0') {
            return;
        }
    
        $this->_orderHelper->addOrder($order, $quote);
    }
}
