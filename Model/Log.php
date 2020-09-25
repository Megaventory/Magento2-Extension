<?php
namespace Mv\Megaventory\Model;

use Magento\Framework\Exception\LocalizedException as CoreException;
use Magento\Framework\DataObject\IdentityInterface;

class Log extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{

    const CACHE_TAG = 'megaventory_log';
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mv\Megaventory\Model\ResourceModel\Log');
    }
    
    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
