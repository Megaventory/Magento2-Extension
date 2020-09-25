<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class SynchronizeTaxes extends \Magento\Backend\App\Action
{

    
    protected $_taxesHelper;
    
    public function __construct(
        \Mv\Megaventory\Helper\Taxes $taxesHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_taxesHelper = $taxesHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_taxesHelper->synchronizeTaxes(false);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('megaventory/index/index');
    }
}
