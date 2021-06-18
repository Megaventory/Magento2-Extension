<?php

namespace Mv\Megaventory\Controller\Adminhtml\Inventory;

class Edit extends \Magento\Backend\App\Action{

    protected $resultPageFactory;
    protected $locationResource;
    protected $locationFactory;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Mv\Megaventory\Model\InventoriesFactory $locationFactory,
        \Mv\Megaventory\Model\ResourceModel\Inventories $locationResource
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->locationFactory = $locationFactory;
        $this->locationResource = $locationResource;
    }

    public function execute()
    {
        /* @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $location = $this->locationFactory->create();
        $this->locationResource->load($location, $this->getRequest()->getParam('id'));

        $pageMainTitle = $resultPage->getLayout()->getBlock('page.title');
        $title = 'Editing Preferences for Megaventory Location "'.$location->getName().'"';
        $pageMainTitle->setPageTitle($title);

        return $resultPage;
    }
}