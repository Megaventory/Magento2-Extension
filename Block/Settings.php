<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mv\Megaventory\Block;

class Settings extends \Magento\Backend\Block\Template
{
    protected $_context;
    protected $_mvHelper;
    protected $_inventoriesHelper;
    protected $_taxesHelper;
    protected $_scopeConfig;
    protected $_sourceRepository;
    protected $_sourceCollection;
    protected $_backendSession;
    
    private $_settings;
    private $_mvConnectivity;
    private $_magentoInstallations;
    
    private $_defaultMagentoCurrency;
    private $_defaultMegaventoryCurrency;
    private $_inventories;
    private $_taxes;

    private $_algorithmConfigSource;
    private $_inventoryCollectionFactory;

    private $_productAttributeCollectionFactory;

    private $_adjustmentTemplateFactory;
    private $_adjustmentTemplateResource;
    private $_adjustmentTemplateCollectionFactory;

    /**
     * @var string
     */
    protected $_template = 'settings.phtml';
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepo,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollection,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $productAttributeCollectionFactory,
        \Mv\Megaventory\Model\ResourceModel\Inventories\CollectionFactory $inventoriesCollectionFactory,
        \Mv\Megaventory\Helper\Data $mvHelper,
        \Mv\Megaventory\Helper\Inventories $inventoriesHelper,
        \Mv\Megaventory\Helper\Taxes $taxesHelper,
        \Mv\Megaventory\Model\Config\Source\SourceSelectionAlgorithmConfigSource $sourceSelectionAlgorithmOptions,
        \Mv\Megaventory\Model\AdjustmentTemplateFactory $adjustmentTemplateFactory,
        \Mv\Megaventory\Model\ResourceModel\AdjustmentTemplate $adjustmentTemplateResource,
        \Mv\Megaventory\Model\ResourceModel\AdjustmentTemplate\CollectionFactory $adjustmentTemplateCollectionFactory
    ) {
        parent::__construct($context);
        
        $this->_context = $context;
        $this->_sourceRepository = $sourceRepo;
        $this->_sourceCollection = $sourceCollection;
        $this->_algorithmConfigSource = $sourceSelectionAlgorithmOptions;
        $this->_mvHelper = $mvHelper;
        $this->_backendSession = $backendSession;
        $this->_inventoriesHelper = $inventoriesHelper;
        $this->_taxesHelper = $taxesHelper;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_inventoryCollectionFactory = $inventoriesCollectionFactory;
        $this->_productAttributeCollectionFactory = $productAttributeCollectionFactory;

        $this->_adjustmentTemplateResource = $adjustmentTemplateResource;
        $this->_adjustmentTemplateFactory = $adjustmentTemplateFactory;
        $this->_adjustmentTemplateCollectionFactory = $adjustmentTemplateCollectionFactory;
        
        $this->_settings = $this->_scopeConfig->getValue('megaventory/general');
        $this->_mvConnectivity = $this->_mvHelper->checkConnectivity();
        $this->_inventories = [];
        $this->_taxes = [];
        
        if ($this->_mvHelper->checkAccount() !== false) {
            $setting = $this->_mvHelper->getMegaventoryAccountSettings('MagentoInstallations');
            $this->_magentoInstallations = $setting['0']['SettingValue'];
            
            $this->_defaultMegaventoryCurrency = $this->setDefaultMegaventoryCurrency();
            $this->_defaultMagentoCurrency = $this->_scopeConfig->getValue('currency/options/default');
            
            $this->_inventories = $this->_inventoriesHelper->getInventories();
            $this->_taxes = $this->_taxesHelper->getTaxes();
        }
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
    }

    public function getInventoryEditUrl($inventory){
        $inventoryId = $inventory->getId();

        $url = $this->getUrl('megaventory/inventory/edit',['id'=>$inventoryId]);
        return $url;
    }

    public function getAttributes()
    {
        return $this->_productAttributeCollectionFactory->create()
                ->addVisibleFilter()
                ->addFieldToFilter('frontend_input', ['in'=>['select','text']]);
    }
    public function getPriceAttributes()
    {
        return $this->_productAttributeCollectionFactory->create()
                ->addVisibleFilter()
                ->addFieldToFilter('frontend_input', 'price');
    }
    public function getAlgorithmList()
    {
        return $this->_algorithmConfigSource->toOptionArray();
    }
    
    public function getSources($inventoryId = false)
    {
        $inventoriesWithSource = $this->_inventoryCollectionFactory->create()
        ->addFieldToSelect('stock_source_code')
        ->addFieldToFilter('stock_source_code', ['notnull'=>true]);
        $allocatedSources = array_values($inventoriesWithSource->toArray()['items']);
        $sources = $this->_sourceCollection->create();
        if (count($allocatedSources) > 0) {
            $sources->addFieldToFilter('source_code', ['nin'=>$allocatedSources]);
        }
        return $sources;
    }

    public function getSource($inventory)
    {
        $result = -1;
        
        if ($inventory->getStockSourceCode() === null) {
            return -1;
        }
        try {
            $result = $this->_sourceRepository->get($inventory->getStockSourceCode());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e){ //Inventory Source Not Found
            $result = -1;
        }
        return $result;
    }

    public function getSettingValue($name)
    {
    
        if (isset($this->_settings[$name])) {
            return $this->_settings[$name];
        } else {
            return '';
        }
    }
    
    public function connectivityOk()
    {
        return $this->_mvConnectivity;
    }
    
    public function getMagentoInstallations()
    {
        return $this->_magentoInstallations;
    }

    public function isSyncStartAllowed(){
        return (!$this->_mvHelper->isSyncStartNotAllowed());
    }
    
    private function setDefaultMegaventoryCurrency()
    {
        if ($this->_mvConnectivity !== true) {
            return false;
        }
    
        $apikey = $this->_scopeConfig->getValue('megaventory/general/apikey');
        $apiurl = $this->_scopeConfig->getValue('megaventory/general/apiurl');
    
        $data =
        [
                'APIKEY' => $apikey,
                'Filters' => [
                                "AndOr" => "And",
                                "FieldName" => "CurrencyIsDefault",
                                "SearchOperator" => "Equals",
                                "SearchValue" => true
                             ]
        ];
            
        $json_result = $this->_mvHelper->makeJsonRequest($data, 'CurrencyGet', 0, $apiurl);
    
        $errorCode = $json_result['ResponseStatus']['ErrorCode'];
        if ($errorCode != '0') {
            return false;
        }
    
        return $json_result['mvCurrencies'][0]['CurrencyCode'];
    }
    
    public function checkBaseCurrencies()
    {
        if ($this->_defaultMagentoCurrency != $this->_defaultMegaventoryCurrency) {
            return false;
        }
    
        return true;
    }
    
    public function getInventories()
    {
        return $this->_inventories;
    }
    
    public function getTaxes()
    {
        return $this->_taxes;
    }

    public function getAdjustmentNegativeTemplates(){
        return $this->_adjustmentTemplateCollectionFactory->create()->addFieldToFilter('stock_change',-1);
    }

    public function getAdjustmentPositiveTemplates(){
        return $this->_adjustmentTemplateCollectionFactory->create()->addFieldToFilter('stock_change',1);
    }

    public function getAdjustmentTemplateName($inventory, $positive = true){
        $templateId = ($positive) ? (int)$inventory->getMvAdjustmentPlusTypeId() : (int)$inventory->getMvAdjustmentMinusTypeId();

        if($templateId > 0){
            $template = $this->_adjustmentTemplateFactory->create();

            $this->_adjustmentTemplateResource->load($template, $templateId);

            return $template->getName();
        }
        return "";
    }
}
