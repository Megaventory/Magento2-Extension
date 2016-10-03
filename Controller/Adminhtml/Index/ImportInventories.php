<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class ImportInventories extends \Magento\Backend\App\Action
{    
	protected $_inventoriesHelper;
	
    public function __construct(
    		\Mv\Megaventory\Helper\Inventories $inventoriesHelper,
    		\Magento\Backend\App\Action\Context $context
    ) {
    	$this->_inventoriesHelper = $inventoriesHelper;
    	parent::__construct($context);
    }

    public function execute()
    {
    	$this->_inventoriesHelper->syncrhonizeInventories();
    	
    	$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
    	return $resultRedirect->setPath('*/*/');
    }
}