<?php

namespace Mv\Megaventory\Model\Config\Source;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

class AvailableInventorySources implements OptionSourceInterface{

    protected $_sourceCollection;
    protected $_inventoryCollectionFactory;
    protected $_request;

    public function __construct(
        \Mv\Megaventory\Model\ResourceModel\Inventories\CollectionFactory $inventoriesCollectionFactory,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollection,
        RequestInterface $requestInterface
    )
    {
        $this->_sourceCollection = $sourceCollection;
        $this->_inventoryCollectionFactory = $inventoriesCollectionFactory;
        $this->_request = $requestInterface;
    }

    public function toOptionArray()
    {
        $currentInventoryId = $this->_request->getParam('id');
        $sources = $this->getAvailableSources($currentInventoryId);
        $options = [];

        $inventory = $this->_inventoryCollectionFactory->create()->addFieldToFilter('id',$currentInventoryId)->getFirstItem();
        if($inventory->getStockSourceCode() == null){
            $options[] = [
                'label'=>' ',
                'value'=>''
            ];
        }

        foreach($sources as $source){
            $options[] = ['label'=>$source->getName(),'value'=>$source->getSourceCode()];
        }

        return $options;
    }

    private function getAvailableSources($inventoryId = -1){
        $inventoriesWithSource = $this->_inventoryCollectionFactory->create()
        ->addFieldToSelect('stock_source_code')
        ->addFieldToFilter('stock_source_code', ['notnull'=>true])
        ->addFieldToFilter('id',['neq'=>$inventoryId]);
        $allocatedSources = array_values($inventoriesWithSource->toArray()['items']);
        $sources = $this->_sourceCollection->create();
        if (count($allocatedSources) > 0) {
            $sources->addFieldToFilter('source_code', ['nin'=>$allocatedSources]);
        }
        return $sources;
    }
}