<?php
namespace Mv\Megaventory\Model\ResourceModel\Inventories;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'id';
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mv\Megaventory\Model\Inventories', 'Mv\Megaventory\Model\ResourceModel\Inventories');
    }
}