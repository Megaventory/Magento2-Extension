<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Mv\Megaventory\Block;

class Settings extends \Magento\Backend\Block\Template
{
	protected $_context;
	protected $_mvHelper;
	protected $_inventoriesHelper;
	protected $_taxesHelper;
	protected $_scopeConfig;
	
	private $_settings;
	private $_mvConnectivity;
	private $_magentoInstallations;
	
	private $_defaultMagentoCurrency;
	private $_defaultMegaventoryCurrency;
	private $_inventories;
	private $_taxes;
    /**
     * @var string
     */
    protected $_template = 'settings.phtml';
    
    public function __construct(
    	\Magento\Backend\Block\Template\Context $context,
    	\Mv\Megaventory\Helper\Data $mvHelper,
    	\Mv\Megaventory\Helper\Inventories $inventoriesHelper,
    	\Mv\Megaventory\Helper\Taxes $taxesHelper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    	)
    {
    	parent::__construct($context);
    	
    	$this->_context = $context;
    	$this->_mvHelper = $mvHelper;
    	$this->_inventoriesHelper = $inventoriesHelper;
    	$this->_taxesHelper = $taxesHelper;
    	$this->_scopeConfig = $scopeConfig;
    	
		$this->_settings = $this->_scopeConfig->getValue('megaventory/general');
    	$this->_mvConnectivity = $this->_mvHelper->checkConnectivity();
    	
    	if ($this->_mvConnectivity !== false){

    		$setting = $this->_mvHelper->getMegaventoryAccountSettings('MagentoInstallations');
    		$this->_magentoInstallations = $setting['0']['SettingValue'];
    		
    		/* if (isset($this->_settings['MagentoInstallations']))
    			$this->_magentoInstallations = $this->_settings['MagentoInstallations'];
    		else
    			$this->_magentoInstallations = 1; */
    		

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
    
    public function getSettingValue($name){
    
    	if (isset($this->_settings[$name]))
    		return $this->_settings[$name];
    	else
    		return '';
    }
    
    public function connectivityOk()
    {
    	return $this->_mvConnectivity;
    }
    
    public function getMagentoInstallations()
    {
    	return $this->_magentoInstallations;
    }
    
    private function setDefaultMegaventoryCurrency()
    {
    	if ($this->_mvConnectivity !== true)
    		return false;
    
    	$apikey = $this->_scopeConfig->getValue('megaventory/general/apikey');
    	$apiurl = $this->_scopeConfig->getValue('megaventory/general/apiurl');
    
    
    	$data = array
    	(
    			'APIKEY' => $apikey,
    			'query' => 'mv.CurrencyIsDefault = 1'
    	);
    		
    	$json_result = $this->_mvHelper->makeJsonRequest($data ,'CurrencyGet',0,$apiurl);
    
    	$errorCode = $json_result['ResponseStatus']['ErrorCode'];
    	if ($errorCode != '0')
    		return false;
    
    	return $json_result['mvCurrencies'][0]['CurrencyCode'];
    }
    
    public function checkBaseCurrencies()
    {
    	if ($this->_defaultMagentoCurrency != $this->_defaultMegaventoryCurrency)
    		return false;
    
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
}
