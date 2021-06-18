<?php

namespace Mv\Megaventory\Model\ResourceModel\DocumentType;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection{

    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Mv\Megaventory\Model\DocumentType', 'Mv\Megaventory\Model\ResourceModel\DocumentType');
    }
}