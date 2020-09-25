<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class UpdateOrderSynchronization extends \Magento\Backend\App\Action
{

    
    protected $_resourceConfig;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_resourceConfig = $resourceConfig;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    public function execute()
    {
        $bCount = $this->getRequest()->getPost('value');
        $bCount == 'true' ? $orderSynchronization = '1' : $orderSynchronization = '0';
        
        $this->_resourceConfig->saveConfig('megaventory/general/ordersynchronization', $orderSynchronization, 'default', 0);
        $this->_cacheTypeList->cleanType('config');
        
        return $this->_resultJsonFactory->create()->setData([]);
    }
}
