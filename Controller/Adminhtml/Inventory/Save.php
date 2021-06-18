<?php

namespace Mv\Megaventory\Controller\Adminhtml\Inventory;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Save extends \Magento\Backend\App\Action implements HttpPostActionInterface{

    protected $inventoryFactory;
    protected $inventoryResource;
    protected $redirectFactory;
    protected $urlBuilder;
    
    public function __construct(
        \Mv\Megaventory\Model\InventoriesFactory $inventoryFactory,
        \Mv\Megaventory\Model\ResourceModel\Inventories $inventoryResource,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        parent::__construct($context);
        $this->inventoryFactory = $inventoryFactory;
        $this->inventoryResource = $inventoryResource;
        $this->redirectFactory = $redirectFactory;
        $this->urlBuilder = $urlBuilder;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParam('inventory_source_associations');
        $params['mv_adjustment_plus_type_id'] = $this->getRequest()->getParam('inventory_adjustment_plus_templates_associations')['mv_adjustment_plus_type_id'];
        $params['mv_adjustment_minus_type_id'] = $this->getRequest()->getParam('inventory_adjustment_minus_templates_associations')['mv_adjustment_minus_type_id'];
        $params['adjustment_doc_status'] = $this->getRequest()->getParam('inventory_adjustment_document_status')['adjustment_doc_status'];
        $id = -1;
        if(array_key_exists('id', $params)){
            $id = $params['id'];
        }

        if($id !== -1){
            $inventory = $this->inventoryFactory->create();
            $this->inventoryResource->load($inventory, $id);
            if($inventory->getId() !== null){
                $inventory->setData($params);
                try{
                    $this->inventoryResource->save($inventory);
                    $this->messageManager->addSuccessMessage('Your preferences for the location "'.$inventory->getName().'" have been saved successfully.');
                }
                catch(\Exception $e){
                    $this->messageManager->addErrorMessage('Unexpected error. Unable to save your preferences. Please try again.');
                }
            }
            else{
                $this->messageManager->addErrorMessage('Unexpected error. Unable to save your preferences. Please try again.');
            }
        }
        else{
            $this->messageManager->addErrorMessage('Unexpected error. Unable to save your preferences. Please try again.');
        }
        $url = $this->urlBuilder->getUrl('megaventory/index/index') . '#locations';
        return $this->redirectFactory->create()->setUrl($url);
    }
}