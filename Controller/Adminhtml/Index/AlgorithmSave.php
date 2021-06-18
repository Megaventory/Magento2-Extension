<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\RedirectFactory;

class AlgorithmSave extends \Magento\Backend\App\Action
{
    protected $_configWriter;
    protected $_messageManager;
    protected $_cacheTypeList;
    protected $_urlBuilder;
    protected $_redirectResultFactory;

    public function __construct(
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\UrlInterface $urlBuilder,
        RedirectFactory $redirectFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_configWriter = $configWriter;
        $this->_messageManager = $messageManager;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_urlBuilder = $urlBuilder;
        $this->_redirectResultFactory = $redirectFactory;
        
        parent::__construct($context);
    }

    public function execute()
    {
        $code = $this->getRequest()->getParam('algorithm_code');
        $result = ['message'=>'Algorithm has been saved','type'=>'success'];
        $path = 'megaventory/orders/source_selection_algorithm_code';
        $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        try {
            $this->_configWriter->save($path, $code, $scope, 0);
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            $result['type'] = 'error';
        }

        try {
            $this->_cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        } catch (\Exception $e) {
            $result['message'] .= ", but unable to refresh configuration values, please clear the magento cache";
        }

        switch ($result['type']) {
            case 'success':
                $this->_messageManager->addSuccessMessage($result['message']);
                break;
            case 'warning':
                $this->_messageManager->addWarningMessage($result['message']);
                break;
            case 'error':
                $this->_messageManager->addErrorMessage($result['message']);
                break;
        }

        $url = $this->_urlBuilder->getUrl('megaventory/index/index') . '#locations';

        return $this->_redirectResultFactory->create()->setUrl($url);
    }
}
