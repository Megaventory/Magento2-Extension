<?php
namespace Mv\Megaventory\Model\ResourceModel;

class Inventories extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('megaventory_inventories', 'id');
    }
}
