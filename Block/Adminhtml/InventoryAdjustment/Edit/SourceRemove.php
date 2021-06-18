<?php

namespace Mv\Megaventory\Block\Adminhtml\InventoryAdjustment\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SourceRemove extends GenericButton implements ButtonProviderInterface
{
    protected $_inventoryResource;
    protected $_inventoryFactory;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        RequestInterface $requestInterface,
        \Mv\Megaventory\Model\InventoriesFactory $inventoriesFactory,
        \Mv\Megaventory\Model\ResourceModel\Inventories $inventoriesResource
    ) {
        parent::__construct($context, $requestInterface);
        $this->_inventoryFactory = $inventoriesFactory;
        $this->_inventoryResource = $inventoriesResource;
    }

    public function getButtonData()
    {
        $data = [];
        if ($this->getId() && $this->shouldDisplay()) {
            $data = [
                'label' => __('Remove Inventory Source Association'),
                'class' => 'action-secondary',
                'on_click' => 'deleteConfirm(\''
                    . __('This will also disable this inventory location until an inventory source is assigned again. Are you sure?')
                    . '\', \'' . $this->getRemovalUrl() . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    public function getRemovalUrl()
    {
        return $this->getUrl('megaventory/inventory/unassigninventorysource', ['id' => $this->getId()]);
    }

    private function shouldDisplay(){
        $inventory = $this->_inventoryFactory->create();
        $this->_inventoryResource->load($inventory, $this->getId());
        return ($inventory->getStockSourceCode() !== null);
    }
}