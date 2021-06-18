<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

class UpdateAccountSettings extends \Magento\Backend\App\Action
{
    
    protected $_resourceConfig;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    protected $_attributeFactory;
    protected $_scopeConfig;
    protected $_mvProductHelper;
    
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Mv\Megaventory\Helper\Product $mvProductHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_resourceConfig = $resourceConfig;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_attributeFactory = $attributeFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_mvProductHelper = $mvProductHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = [
            'success'=>true
        ];
        
        $magentoId = $this->getRequest()->getParam('magento_id');
        $supplierAttributeCode = $this->getRequest()->getParam('magento_supplier_attribute_code');
        $shippingProductSku = $this->getRequest()->getParam('shipping_product_sku');
        $discountProductSku = $this->getRequest()->getParam('discount_product_sku');
        $currentShippingProductSku = $this->_scopeConfig->getValue('megaventory/general/shippingproductsku');
        $currentDiscountProductSku = $this->_scopeConfig->getValue('megaventory/general/discountproductsku');
        $cleanCache = false;
        $purchasePriceAttributeCode = $this->getRequest()->getParam('purchase_price_attribute_code');
        
        if (!empty($magentoId)) {
            $this->_resourceConfig->saveConfig('megaventory/general/magentoid', $magentoId, 'default', 0);
            $cleanCache = true;
        }
        if(!empty($supplierAttributeCode)){
            $attribute = $this->_attributeFactory->create()->loadByCode('catalog_product', $supplierAttributeCode);
            
            if ($attribute->getId()) {
                $this->_resourceConfig->saveConfig('megaventory/general/supplierattributecode', $supplierAttributeCode, 'default', 0);
                $cleanCache = true;
            }
        }

        if(!empty($discountProductSku) && ($discountProductSku != $currentDiscountProductSku)){
            $productAddedToMegaventory = $this->_mvProductHelper->addOrUpdateDiscountProduct($discountProductSku);
            if (is_bool($productAddedToMegaventory) && ($productAddedToMegaventory === true)){
                $this->_resourceConfig->saveConfig('megaventory/general/discountproductsku', $discountProductSku, 'default', 0);
                $cleanCache = true;
            }
            
        }

        if(!empty($shippingProductSku) && ($shippingProductSku != $currentShippingProductSku)){
            $productAddedToMegaventory = $this->_mvProductHelper->addOrUpdateShippingProduct($shippingProductSku);
            if (is_bool($productAddedToMegaventory) && ($productAddedToMegaventory === true)) {
                $this->_resourceConfig->saveConfig('megaventory/general/shippingproductsku', $shippingProductSku, 'default', 0);
                $cleanCache = true;
            }
        }

        $this->_resourceConfig->saveConfig('megaventory/general/purchasepriceattributecode', $purchasePriceAttributeCode, 'default', 0);

        if($cleanCache){
            $this->_cacheTypeList->cleanType('config');
        }
        return $this->_resultJsonFactory->create()->setData($result);
    }
}