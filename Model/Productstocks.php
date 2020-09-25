<?php
namespace Mv\Megaventory\Model;

use Magento\Framework\Exception\LocalizedException as CoreException;
use Magento\Framework\DataObject\IdentityInterface;

class Productstocks extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{

    const CACHE_TAG = 'megaventory_stock';
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mv\Megaventory\Model\ResourceModel\Productstocks');
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
    
    public function loadInventoryProductstock($inventoryId, $productId)
    {
        return $this->getCollection()
        ->addFieldToFilter('product_id', $productId)
        ->addFieldToFilter('inventory_id', $inventoryId)
        ->load()->getFirstItem();
    }
    
    public function loadProductstocks($productId)
    {
        return $this->getCollection()
        ->addFieldToFilter('product_id', $productId)
        ->load();
    }
}
