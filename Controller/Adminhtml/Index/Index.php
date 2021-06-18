<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Mv\Megaventory\Helper\Data;
use Mv\Megaventory\Helper\Inventories;
use Mv\Megaventory\Model\ApiAdapter\DocumentTypeAdapter;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    protected $inventoryHelper;
    protected $documentTypeAdapter;
    protected $mvHelper;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        DocumentTypeAdapter $documentTypeAdapter,
        Inventories $inventoryHelper,
        Data $mvHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->inventoryHelper = $inventoryHelper;
        $this->documentTypeAdapter = $documentTypeAdapter;
        $this->mvHelper = $mvHelper;
    }

    public function execute()
    {
        /* @var \Magento\Backend\Model\View\Result\Page $resultPage */
        if(($this->mvHelper->checkConnectivity() === true) && ($this->mvHelper->checkAccount())){
            $this->inventoryHelper->updateInventoryLocations();
            $this->documentTypeAdapter->reloadAdjustmentTemplatesFromApi();
        }
        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}
