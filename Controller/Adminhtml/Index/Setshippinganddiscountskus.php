<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Mv\Megaventory\Helper\Data;
use Mv\Megaventory\Helper\Inventories;
use Mv\Megaventory\Model\ApiAdapter\DocumentTypeAdapter;

class Setshippinganddiscountskus extends \Magento\Backend\App\Action
{
    protected $_resourceConfig;
    protected $_attributeFactory;
    protected $_resultJsonFactory;
    protected $_cacheTypeList;
    protected $_mvDataHelper;
    protected $_scopeConfig;
    protected $_backendSession;
    protected $_mvInventoryHelper;
    protected $_mvDocumentTypeAdapter;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Backend\Model\Session $backendSession,
        ScopeConfigInterface $scopeConfigInterface,
        Data $mvDataHelper,
        Inventories $mvIntentoryHelper,
        DocumentTypeAdapter $documentTypeAdapter
    ) {
        $this->_resourceConfig = $resourceConfig;
        $this->_attributeFactory = $attributeFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_scopeConfig = $scopeConfigInterface;
        $this->_mvDataHelper = $mvDataHelper;
        $this->_backendSession = $backendSession;
        $this->_mvInventoryHelper = $mvIntentoryHelper;
        $this->_mvDocumentTypeAdapter =$documentTypeAdapter;
        parent::__construct($context);
    }

    public function execute()
    {
        $shippingSKU = $this->getRequest()->getParam('shippingSKU');
        $discountSKU = $this->getRequest()->getParam('discountSKU');
        $magentoId = $this->getRequest()->getParam('magentoId');
        $purchasePriceAttributeCode = $this->getRequest()->getParam('purchase_price_attribute_code');

        $isSyncStartNotAllowed = $this->_mvDataHelper->isSyncStartNotAllowed();

        if($isSyncStartNotAllowed){
            $result = ['message'=>'Initial Synchronization is running','code'=>'initial_sync_runs'];
            return $this->_resultJsonFactory->create()->setData($result);
        }



        if((int)$this->_scopeConfig->getValue('megaventory/synchronization/started') == 1){
            $this->_resourceConfig->deleteConfig('megaventory/general/synctimestamp', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/general/shippingproductsku', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/general/discountproductsku', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/general/magentoid', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/general/supplierattributecode', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/general/defaultguestid', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/general/syncreport', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/general/setupreport', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/general/ordersynchronization', 'default', 0);
            $this->_resourceConfig->deleteConfig('megaventory/feed/last_update', 'default', 0);
            $this->_cacheTypeList->cleanType('config');
        
            $this->_mvDataHelper->resetMegaventoryData();

            $this->_mvInventoryHelper->updateInventoryLocations();

            $this->_mvDocumentTypeAdapter->reloadAdjustmentTemplatesFromApi();
        }
        
        $this->_resourceConfig->saveConfig('megaventory/general/shippingproductsku', $shippingSKU, 'default', 0);
        $this->_resourceConfig->saveConfig('megaventory/general/discountproductsku', $discountSKU, 'default', 0);
        $this->_resourceConfig->saveConfig('megaventory/general/magentoid', $magentoId, 'default', 0);
        $this->_resourceConfig->saveConfig('megaventory/general/purchasepriceattributecode', $purchasePriceAttributeCode, 'default', 0);
        
        //add supplier attribute in config data
        $magentoSupplierAttributeCode = $this->getRequest()->getParam('magento_supplier_code');
        
        if (!empty($magentoSupplierAttributeCode)) {
            $attribute = $this->_attributeFactory->create()->loadByCode('catalog_product', $magentoSupplierAttributeCode);
                
            if (!$attribute->getId()) {
                $result = [
                        'attribute_code'=>'notok',
                        'message'=>'There is no attribute with this code'
                ];
                return $this->_resultJsonFactory->create()->setData($result);
            }
                
            $frontendInput = $attribute->getFrontendInput();
                
            if ($frontendInput != 'text' && $frontendInput != 'select') {
                $result = [
                        'attribute_code'=>'notok',
                        'message'=>'Supplier attribute must be of frontend type Text or Dropdown'
                ];
                return $this->_resultJsonFactory->create()->setData($result);
            }
        
            $this->_resourceConfig->saveConfig('megaventory/general/supplierattributecode', $magentoSupplierAttributeCode, 'default', 0);
        }
        
        $this->_cacheTypeList->cleanType('config');
        
        $result = [
                'attribute_code'=>'ok'
        ];

        $this->_mvDataHelper->saveInitialSyncFlags();
        return $this->_resultJsonFactory->create()->setData($result);
    }
}
