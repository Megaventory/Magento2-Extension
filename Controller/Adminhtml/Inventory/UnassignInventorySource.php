<?php

namespace Mv\Megaventory\Controller\Adminhtml\Inventory;

class UnassignInventorySource extends \Magento\Backend\App\Action
{
    private $_mvInventoryFactory;
    private $_mvInventoryResource;
    private $_resultRedirectFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Mv\Megaventory\Model\InventoriesFactory $mvInventoryFactory,
        \Mv\Megaventory\Model\ResourceModel\Inventories $mvInventoryResource
    ) {
        $this->_mvInventoryFactory = $mvInventoryFactory;
        $this->_resultRedirectFactory = $redirectFactory;
        $this->_mvInventoryResource = $mvInventoryResource;

        parent::__construct($context);
    }

    public function execute()
    {
        $inventoryId = (int)$this->getRequest()->getParam('id');
        $mvInventory = $this->_mvInventoryFactory->create();
        $this->_mvInventoryResource->load($mvInventory, $inventoryId);
        try {
            $mvInventory->setData('stock_source_code');
            $mvInventory->setCountsInGlobalStock(0);
            $this->_mvInventoryResource->save($mvInventory);
            $this->messageManager->addSuccessMessage('Removed inventory source from megaventory location "'.$mvInventory->getName().'"');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Unexpected error. Unable to save your preferences. Please try again later.');
        }

        return $this->_resultRedirectFactory->create()->setPath('megaventory/inventory/edit',['id'=>$mvInventory->getId()]);
    }
}
