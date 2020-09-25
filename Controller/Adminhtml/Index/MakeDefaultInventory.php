<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class MakeDefaultInventory extends \Magento\Backend\App\Action
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
        $inventoryId = $this->getRequest()->getPost('inventoryId');
        
        $this->_inventoriesHelper->makeDefaultInventory($inventoryId);
        
        return $this->_resultJsonFactory->create()->setData([]);
        
        /* $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('megaventory/index/index'); */
    }
}
