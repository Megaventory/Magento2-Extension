<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class Savesettings extends \Magento\Backend\App\Action
{
	protected $_resourceConfig;
	protected $_cacheTypeList;
    
    public function __construct(
    		\Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
    	\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
    	$this->_resourceConfig = $resourceConfig;
    	$this->_cacheTypeList = $cacheTypeList;
    	parent::__construct($context);
    }

    public function execute()
    {
    	$enabled = $this->getRequest()->getParam('megaventory_enabled');
    	$apiurl = $this->getRequest()->getParam('megaventory_apiurl');
    	$apikey = $this->getRequest()->getParam('megaventory_apikey');

    	$this->_resourceConfig->saveConfig('megaventory/general/enabled',$enabled,'default',0);
    	$this->_resourceConfig->saveConfig('megaventory/general/apiurl',$apiurl,'default',0);
    	$this->_resourceConfig->saveConfig('megaventory/general/apikey',$apikey,'default',0);
    	
    	$this->_cacheTypeList->cleanType('config');
    	
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}