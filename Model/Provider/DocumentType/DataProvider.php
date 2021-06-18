<?php

namespace Mv\Megaventory\Model\Provider\DocumentType;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{ 
    public function __construct(        
        \Mv\Megaventory\Model\ResourceModel\DocumentType\Collection $collection,
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
            $this->loadedData[$item->getId()]['website_associations_general']['website_associations'] = $item->getData();
        }


        return $this->loadedData;
    }
}