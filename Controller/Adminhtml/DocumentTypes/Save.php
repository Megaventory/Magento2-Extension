<?php

namespace Mv\Megaventory\Controller\Adminhtml\DocumentTypes;

use Magento\Framework\Message\MessageInterface;

class Save extends \Magento\Backend\App\Action{

    protected $documentTypeResource;
    protected $documentTypeFactory;
    protected $documentTypeCollectionFactory;
    protected $request;
    protected $redirectFactory;
    protected $documentTypeHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Mv\Megaventory\Model\DocumentTypeFactory $documentTypeFactory,
        \Mv\Megaventory\Model\ResourceModel\DocumentType $documentTypeResource,
        \Mv\Megaventory\Model\ResourceModel\DocumentType\CollectionFactory $documentTypeCollectionFactory,
        \Mv\Megaventory\Helper\DocumentType $documentTypeHelper,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        parent::__construct($context);
        $this->documentTypeFactory = $documentTypeFactory;
        $this->documentTypeResource = $documentTypeResource;
        $this->documentTypeCollectionFactory = $documentTypeCollectionFactory;
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->documentTypeHelper = $documentTypeHelper;
    }

    public function execute()
    {
        $params = $this->_request->getParam('website_associations_general')['website_associations'];

        if(($params === null) || ! in_array('id', array_keys($params))){
            $this->messageManager->addErrorMessage('Invalid Request.');
            return $this->redirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
        }

        $id = (in_array('id', array_keys($params))) ? (int)$params['id'] : null;

        if(null === $id || empty($id) || ($id === 0)){
            $this->messageManager->addErrorMessage('An unexpected error occurred and the entity could not be saved.');
            return $this->redirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
        }

        $websiteIds = (in_array('magento_website_ids', array_keys($params)) && is_array($params['magento_website_ids'])) ? $params['magento_website_ids'] : [];

        $removedFromOtherTypes = $this->documentTypeHelper->removeWebsiteFromOtherTypes($websiteIds, [$id]);

        $currentAssociations = $this->documentTypeHelper->getAssociations($id);

        $assignedOrphansToDefault = $this->documentTypeHelper->assignOrphanedWebsitesToDefaultDocumentType($websiteIds, $currentAssociations);


        if(!$removedFromOtherTypes || !$assignedOrphansToDefault){
            $this->messageManager->addErrorMessage('An unexpected error occurred and the entity could not be saved.');
            return $this->redirectFactory->create()->setPath('*/*/index');
        }
        
        $update = $this->documentTypeHelper->updateWebsiteAssociations($websiteIds, $id);

        if(!$update){
            $this->messageManager->addErrorMessage('Unable to save entity.');
            return $this->redirectFactory->create()->setPath('*/*/index');
        }
        
        $this->messageManager->addSuccessMessage('The order template has been assigned to the respective Magento websites successfully.');
        return $this->redirectFactory->create()->setPath('*/*/index');
    }
}