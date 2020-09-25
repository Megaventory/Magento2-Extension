<?php
namespace Mv\Megaventory\Model;

use Magento\Framework\Exception\LocalizedException as CoreException;
use Magento\Framework\DataObject\IdentityInterface;

class Bom extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{

    const CACHE_TAG = 'megaventory_bom';
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mv\Megaventory\Model\ResourceModel\Bom');
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
    
    public function loadByBOMCode($bundleCode)
    {
        return $this->load($bundleCode, 'auto_code');
    }
    
    public function loadByBOMSku($bundleSku)
    {
        return $this->load($bundleSku, 'megaventory_sku');
    }
}
