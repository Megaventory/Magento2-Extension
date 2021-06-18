<?php

namespace Mv\Megaventory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DocumentType extends AbstractDb{
    protected function _construct()
    {
        $this->_init('megaventory_order_templates', 'id');
    }
}