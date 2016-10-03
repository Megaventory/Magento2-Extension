<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

class ExportStock extends \Magento\Backend\App\Action
{
	protected $_inventoriesLoader;
	protected $_mvProductHelper;
	protected $_resultJsonFactory;
	protected $_cacheTypeList;
	protected $_directoryList;
    
    public function __construct(
    	\Magento\Backend\App\Action\Context $context,
    	\Mv\Megaventory\Model\InventoriesFactory $inventoriesLoader,
    	\Mv\Megaventory\Helper\Product $mvProductHelper,
    	\Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    	\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
    	$this->_inventoriesLoader = $inventoriesLoader;
    	$this->_mvProductHelper = $mvProductHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
    	$this->_cacheTypeList = $cacheTypeList;
    	$this->_directoryList = $directoryList;
        
    	parent::__construct($context);
    }

    public function execute()
    {
    	$inventoryId = $this->getRequest()->getParam('inventory');
    	if (isset($inventoryId)){
    		$inventory = $this->_inventoriesLoader->create()->load($inventoryId);
    		$inventoryName = $inventory->getName().' ('.$inventory->getShortname().')';
    			
    		$filePath = $this->_mvProductHelper->exportStock($inventoryName, $this->_directoryList);
    			
    		$result = array(
    				'filePath'=>$filePath,
    				'message'=>'No inventory found'
    		);
    	}
    	else
    	{
    		$result = array('message'=>'No inventory found');
    	}
    	return $this->_resultJsonFactory->create()->setData($result);
    }
}