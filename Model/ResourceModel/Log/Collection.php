<?php
namespace Mv\Megaventory\Model\ResourceModel\Log;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'log_id';
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mv\Megaventory\Model\Log', 'Mv\Megaventory\Model\ResourceModel\Log');
    }
}