<?php

namespace Mv\Megaventory\Controller\Adminhtml\Index;

class ExportStock extends \Magento\Backend\App\Action
{
    protected $_inventoriesLoader;
    protected $_mvProductHelper;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    protected $_directoryList;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
        \Mv\Megaventory\Helper\Product $mvProductHelper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->_inventoriesLoader = $inventoriesLoader;
        $this->_mvProductHelper = $mvProductHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_directoryList = $directoryList;

        parent::__construct($context);
    }

    public function execute()
    {
        $inventoryId = $this->getRequest()->getParam('inventory');
        $startingIndex = (int) $this->getRequest()->getParam('startingIndex');
        $results = [];

        if (isset($inventoryId)) {
            $inventory = $this->_inventoriesLoader->create()->load($inventoryId);
            $inventoryName = $inventory->getName() . ' (' . $inventory->getShortname() . ')';
            $inventoryMvId = (int) $inventory->getMegaventoryId();
            $results = $this->_mvProductHelper->exportStock($inventoryMvId, $startingIndex, $this->_directoryList);
        } else {
            $results = [
                'value' => 'Error',
                'message' => 'No inventory found',
                'startingIndex' => $startingIndex
            ];
        }

        return $this->_resultJsonFactory->create()->setData($results);
    }
}
