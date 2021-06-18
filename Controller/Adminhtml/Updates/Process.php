<?php
namespace Mv\Megaventory\Controller\Adminhtml\Updates;

use Magento\Backend\App\Action\Context;

class Process extends \Magento\Backend\App\Action
{

    protected $_megaventoryService;
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Mv\Megaventory\Model\Services\MegaventoryService $megaventoryService
    ) {
        $this->_megaventoryService = $megaventoryService;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $this->_megaventoryService->applyPendingUpdates();
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->messageManager->addSuccessMessage('Pending updates have been applied successfully.');
        return $resultRedirect->setPath('megaventory/log/index');
    }
}
