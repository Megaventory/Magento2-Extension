<?php

namespace Mv\Megaventory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AdjustmentTemplate extends AbstractDb{
    protected function _construct()
    {
        $this->_init('megaventory_adjustment_templates', 'id');
    }
}