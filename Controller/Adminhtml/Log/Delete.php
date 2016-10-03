<?php
namespace Mv\Megaventory\Controller\Adminhtml\Log;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Mv\Megaventory\Model\ResourceModel\Log\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 */
class Delete  extends \Magento\Backend\App\Action
{
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
    	return true;
        //return $this->_authorization->isAllowed('Ashsmith_Blog::delete');
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('log_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->_objectManager->create('Mv\Megaventory\Model\Log');
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The log has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/', ['log_id' => $id]);
            }
        }
        $this->messageManager->addError(__('We can\'t find the log to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}