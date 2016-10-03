<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mv\Megaventory\Block\Adminhtml\Product\Form;

class Megaventory extends \Magento\Backend\Block\Template
{
	protected $inventoriesHelper;
	protected $productHelper;
	protected $productStocksLoader;
	protected $adminSession;
	protected $registry;
	protected $form;
	
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
    	\Mv\Megaventory\Helper\Inventories $inventoriesHelper,
    	\Mv\Megaventory\Helper\Product $productHelper,	
    	\Mv\Megaventory\Model\ProductstocksFactory $productStocksLoader,
    	\Magento\Backend\Model\Auth\Session $adminSession,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\Form $form,
        $data = []
    ) {
    	$this->inventoriesHelper = $inventoriesHelper;
    	$this->productHelper = $productHelper;
    	$this->productStocksLoader = $productStocksLoader;
    	$this->adminSession = $adminSession;
        $this->registry = $registry;
        $this->form = $form;
        $this->setTemplate('product/form/megaventory.phtml');
        parent::__construct($context, $data);
    }
    
    public function getCurrentProduct(){
    	return $this->registry->registry('current_product');
    }
    
    public function getInventories(){
    	return $this->inventoriesHelper->getInventories();
    }
    
    public function getAdminSessionValue($key){
    	return $this->adminSession->getData($key);
    }
    
	public function getInventoryProductstock($inventoryId, $productId)
    {
    	return $this->productStocksLoader->create()->loadInventoryProductstock($inventoryId, $productId);
    }
    
    public function getMvProduct($mvProductId){
    	return $this->productHelper->getProduct($mvProductId);
    }
}
