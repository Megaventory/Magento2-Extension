<?php

namespace Mv\Megaventory\Model\Provider\InventoryAdjustment;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{ 

    public function __construct(        
        \Mv\Megaventory\Model\ResourceModel\Inventories\Collection $collection,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []        
    ) {
        $this->collection = $collection;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = array();
        foreach ($items as $item) {
            $this->loadedData[$item->getId()]['inventory_adjustment_plus_templates_associations'] = $item->getData();
            $this->loadedData[$item->getId()]['inventory_adjustment_minus_templates_associations'] = $item->getData();
            $this->loadedData[$item->getId()]['inventory_source_associations'] = $item->getData();
            $this->loadedData[$item->getId()]['inventory_adjustment_document_status'] = $item->getData();
        }


        return $this->loadedData;
    }
}