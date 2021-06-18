<?php

namespace Mv\Megaventory\Helper;

class DocumentType{

    protected $documentTypeCollectionFactory;
    protected $documentTypeResource;
    protected $documentTypeFactory;

    const DEFAULT_ORDER_DOCUMENT_TYPE = 3;

    public function __construct(
        \Mv\Megaventory\Model\DocumentTypeFactory $documentTypeFactory,
        \Mv\Megaventory\Model\ResourceModel\DocumentType $documentTypeResource,
        \Mv\Megaventory\Model\ResourceModel\DocumentType\CollectionFactory $documentTypeCollectionFactory
    )
    {
        $this->documentTypeFactory = $documentTypeFactory;
        $this->documentTypeResource = $documentTypeResource;
        $this->documentTypeCollectionFactory = $documentTypeCollectionFactory;
    }

    public function getDocumentTypeIdFromWebsiteId($websiteId){
        $documentTypes = $this->documentTypeCollectionFactory->create()
            ->addFieldToFilter('magento_website_ids', ['finset'=>$websiteId]);
        if(count($documentTypes) == 0) return self::DEFAULT_ORDER_DOCUMENT_TYPE;
        return $documentTypes
            ->getFirstItem()->getMegaventoryId();
    }

    public function removeWebsiteFromOtherTypes($websiteIds, $exculdedIds = []){
        foreach($websiteIds as $websiteId){
            $currentlyAssignedTypes = $this->documentTypeCollectionFactory->create()->addFieldToFilter('magento_website_ids',['finset'=>$websiteId])->addFieldToFilter('id',['nin'=>$exculdedIds]);
            if((count($currentlyAssignedTypes) > 0)){
                foreach($currentlyAssignedTypes as $documentType){
                    $assignedWebsites = explode(',', $documentType->getMagentoWebsiteIds());
                    $key = array_search($websiteId, $assignedWebsites);
                    
                    unset($assignedWebsites[$key]);
                    $documentType->setMagentoWebsiteIds(trim(implode(',', $assignedWebsites),','));
                    try{
                        $this->documentTypeResource->save($documentType);
                    }
                    catch(\Exception $e){
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function assignOrphanedWebsitesToDefaultDocumentType($currentAssociations, $previousAssociations){
        $defaultType = $this->documentTypeFactory->create();
        $this->documentTypeResource->load($defaultType, 3, 'megaventory_id');

        $defaultDocumentTypeWebsites = explode(',', $defaultType->getMagentoWebsiteIds());
        foreach($previousAssociations as $websiteId){
            if((!in_array($websiteId, $currentAssociations) && !in_array($websiteId, $defaultDocumentTypeWebsites) && !empty($websiteId))){
                $defaultDocumentTypeWebsites[] = $websiteId;
            }
        }

        $defaultType->setMagentoWebsiteIds(trim(implode(',', $defaultDocumentTypeWebsites),','));

        try{
            $this->documentTypeResource->save($defaultType);
        }
        catch(\Exception $e){
            return false;
        }

        return true;
    }

    public function getAssociations($id){
        $documentTypeModel = $this->documentTypeFactory->create();
        $this->documentTypeResource->load($documentTypeModel, $id);
        return explode(',', $documentTypeModel->getMagentoWebsiteIds());
    }
    public function updateWebsiteAssociations($websiteIds, $id){
        $documentTypeModel = $this->documentTypeFactory->create();
        $this->documentTypeResource->load($documentTypeModel, $id);
        
        $documentTypeModel->setMagentoWebsiteIds(implode(',', $websiteIds));
        try{
            $this->documentTypeResource->save($documentTypeModel);
        }
        catch(\Exception $e){
            return false;
        }
        return true;
    }
}