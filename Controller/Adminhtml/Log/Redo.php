<?php
namespace Mv\Megaventory\Controller\Adminhtml\Log;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Mv\Megaventory\Model\ResourceModel\Log\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 */
class Redo  extends \Magento\Backend\App\Action
{

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
    	return true;
        //return $this->_authorization->isAllowed('Ashsmith_Blog::save');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('log_id');
        $model = $this->_objectManager->create('Mv\Megaventory\Model\Log');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This log no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
            

            $model->delete();
            $this->messageManager->addSuccess(__('The log has been deleted.'));
            return $resultRedirect->setPath('*/*/');
        }
    }
}