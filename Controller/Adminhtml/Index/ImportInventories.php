<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class ImportInventories extends \Magento\Backend\App\Action
{    
	protected $_inventoriesHelper;
	protected $_resultJsonFactory;
	
    public function __construct(
    		\Mv\Megaventory\Helper\Inventories $inventoriesHelper,
        	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    		\Magento\Backend\App\Action\Context $context
    ) {
    	$this->_inventoriesHelper = $inventoriesHelper;
    	$this->_resultJsonFactory = $resultJsonFactory;
    	
    	parent::__construct($context);
    }

    public function execute()
    {
    	$page = $this->getRequest()->getPost('page');
    	
    	$nextPage = $this->_inventoriesHelper->syncrhonizeInventories(-1, -1, $page);
    	
    	return $this->_resultJsonFactory->create()->setData(array('page'=>$nextPage));
    	
    }
}