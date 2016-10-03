<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

class ResetSetup extends \Magento\Backend\App\Action
{
	protected $_resourceConfig;
	protected $_cacheTypeList;
	protected $_resultJsonFactory;
    
    public function __construct(
    	\Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
    	\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
    	$this->_resourceConfig = $resourceConfig;
    	$this->_cacheTypeList = $cacheTypeList;
    	$this->_resultJsonFactory = $resultJsonFactory;
        
    	parent::__construct($context);
    }

    public function execute()
    {
  		$this->_resourceConfig->deleteConfig('megaventory/general/synctimestamp', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/general/shippingproductsku', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/general/discountproductsku', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/general/magentoid', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/general/supplierattributecode', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/general/defaultguestid', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/general/syncreport', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/general/setupreport', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/general/ordersynchronization', 'default', 0);
    	$this->_resourceConfig->deleteConfig('megaventory/feed/last_update', 'default', 0);
    	$this->_cacheTypeList->cleanType('config');
    
    	$this->_mvHelper->resetMegaventoryData();
    	
   		return $this->_resultJsonFactory->create();
    }
}