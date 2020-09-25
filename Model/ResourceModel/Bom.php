<?php
namespace Mv\Megaventory\Model\ResourceModel;

class Bom extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('megaventory_bom', 'id');
    }
}
