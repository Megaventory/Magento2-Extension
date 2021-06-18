<?php
namespace Mv\Megaventory\Controller\Adminhtml\DocumentTypes;

use Mv\Megaventory\Helper\Data;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    protected $mvHelper;
    protected $documentTypeAdapter;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Mv\Megaventory\Model\ApiAdapter\DocumentTypeAdapter $documentTypeAdapter,
        Data $mvHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->mvHelper = $mvHelper;
        $this->documentTypeAdapter = $documentTypeAdapter;
    }

    public function execute()
    {
        /* @var \Magento\Backend\Model\View\Result\Page $resultPage */
        if(($this->mvHelper->checkConnectivity() === true) && ($this->mvHelper->checkAccount())){
            $this->documentTypeAdapter->reloadDocumentTypesFromApi();
        }
        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}