<?php

namespace Mv\Megaventory\Controller\Adminhtml\Index;

class ExportStock extends \Magento\Backend\App\Action
{
    protected $_inventoriesLoader;
    protected $_inventoriesCollection;
    protected $_mvProductHelper;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    protected $_directoryList;
    protected $_mvInventoryResource;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
        \Mv\Megaventory\Helper\Product $mvProductHelper,
        \Mv\Megaventory\Model\ResourceModel\Inventories\CollectionFactory $inventoriesCollection,
        \Mv\Megaventory\Model\ResourceModel\Inventories $inventoriesResource,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->_inventoriesLoader = $inventoriesLoader;
        $this->_mvProductHelper = $mvProductHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_directoryList = $directoryList;
        $this->_inventoriesCollection = $inventoriesCollection;
        $this->_mvInventoryResource = $inventoriesResource;

        parent::__construct($context);
    }

    public function execute()
    {
        $enabledLocations = $this->_inventoriesCollection->create()->addFieldToFilter('stock_source_code',['notnull'=>true]);
        $startingIndex = (int) $this->getRequest()->getParam('startingIndex');
        $isInitialization = ((int)$this->getRequest()->getParam('init') === 1);
        $adjIssued = ($this->getRequest()->getParam('adjustment') == 'true');

        $results = [];

        if ($isInitialization) {
            $locations = $this->getRequest()->getParam('locations');

            foreach ($locations as $mvInventoryId => $preferences) {
                $location = $this->_inventoriesLoader->create();
                $this->_mvInventoryResource->load($location, $mvInventoryId, 'megaventory_id');
                $location->setStockSourceCode($preferences['source']);
                $location->setMvAdjustmentMinusTypeId($preferences['minus_template']);
                $location->setMvAdjustmentPlusTypeId($preferences['plus_template']);
                $location->setAdjustmentDocStatus($preferences['status']);
                $this->_mvInventoryResource->save($location);
                if($this->getRequest()->getParam('export_stock',false)){
                    $results = $this->_mvProductHelper->exportStock(
                        $mvInventoryId,
                        $startingIndex,
                        $this->_directoryList,
                        $location->getStockSourceCode(),
                        $adjIssued
                    );
                    if ($results['value'] == 'Error') {
                        break;
                    }
                }
                else{
                    $results = [
                        'value' => 'Success',
                        'message' => 'No adjustment was necessary',
                        'startingIndex' => $startingIndex
                    ];
                }
            }
        } else {
            foreach ($enabledLocations as $location) {
                $results = $this->_mvProductHelper->exportStock(
                    $location->getMegaventoryId(),
                    $startingIndex,
                    $this->_directoryList,
                    $location->getStockSourceCode(),
                    $adjIssued
                );
                if ($results['value'] == 'Error') {
                    break;
                }
                else{
                    if($results['value'] === 'Continue'){
                        $adjIssued = $results['adjMade'];
                    }
                }
            }
        }

        return $this->_resultJsonFactory->create()->setData($results);
    }
}
