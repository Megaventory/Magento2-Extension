<?php

namespace Mv\Megaventory\Model\ApiAdapter;

use Magento\Store\Model\StoreManagerInterface;
use Mv\Megaventory\Model\AdjustmentTemplate;
use Mv\Megaventory\Model\ResourceModel\AdjustmentTemplate as ResourceModelAdjustmentTemplate;
use Mv\Megaventory\Model\ResourceModel\AdjustmentTemplate\CollectionFactory;
use Mv\Megaventory\Model\ResourceModel\DocumentType\CollectionFactory as DocumentTypeCollectionFactory;

class DocumentTypeAdapter{

    private $_mappings;
    private $_filters;
    
    protected $_documentTypeFactory;
    protected $_documentTypeResource;
    protected $_documentTypeCollectionFactory;
    protected $_adjustmentTemplateFactory;
    protected $_adjustmentTemplateResource;
    protected $_adjustmentTemplateCollectionFactory;
    protected $_mvHelper;
    protected $_storeManager;

    protected function initializeMappings(){
        $this->_mappings = ['SalesOrder'=>
            [
                "DocumentTypeID" => "megaventory_id",
                "DocumentTypeAbbreviation" => "shortname",
                "DocumentTypeDescription" => "name"
            ],
            'Adjustment'=>
            [
                "DocumentTypeID" => "megaventory_id",
                "DocumentTypeAbbreviation" => "shortname",
                "DocumentTypeDescription" => "name",
                'DocumentTypeStockChange'=> "stock_change"
            ]
        ];
    }

    protected function initializeFilters(){
        $this->_filters = ['SalesOrder'=>
            [
                'FieldName'=>'IsSalesOrder',
                'SearchOperator'=>'Equals',
                'SearchValue'=>true
            ],
            'Adjustment'=>[
                [
                    'FieldName'=>'IsAdjustment',
                    'SearchOperator'=>'Equals',
                    'SearchValue'=>true
                ]
            ]
        ];
    }

    protected function getMappings($type){
        return $this->_mappings[$type];
    }

    protected function getFilters($type){
        return $this->_filters[$type];
    }

    protected function getAllWebsiteIds(){
        $result = [];
        $websites = $this->_storeManager->getWebsites();
        foreach($websites as $website){
            $result[] = $website->getId();
        }
        return $result;
    }

    public function __construct(
        \Mv\Megaventory\Model\DocumentTypeFactory $documentTypeFactory,
        \Mv\Megaventory\Model\ResourceModel\DocumentType $documentTypeResource,
        \Mv\Megaventory\Model\ResourceModel\DocumentType\CollectionFactory $documentTypeCollectionFactory,
        \Mv\Megaventory\Helper\Data $mvHelper,
        \Mv\Megaventory\Model\AdjustmentTemplateFactory $adjustmentTemplateFactory,
        \Mv\Megaventory\Model\ResourceModel\AdjustmentTemplate\CollectionFactory $adjustmentTemplateCollectionFactory,
        ResourceModelAdjustmentTemplate $adjustmentTemplateResource,
        StoreManagerInterface $storeManager
    )
    {
        $this->_documentTypeFactory = $documentTypeFactory;
        $this->_documentTypeResource = $documentTypeResource;
        $this->_documentTypeCollectionFactory = $documentTypeCollectionFactory;
        $this->_adjustmentTemplateFactory = $adjustmentTemplateFactory;
        $this->_adjustmentTemplateResource = $adjustmentTemplateResource;
        $this->_adjustmentTemplateCollectionFactory = $adjustmentTemplateCollectionFactory;
        $this->_mvHelper = $mvHelper;
        $this->_storeManager = $storeManager;
        $this->initializeFilters();
        $this->initializeMappings();
    }

    public function convertApiDataToModelData($apiData, $type){
        $data = [];

        $mappings = $this->getMappings($type);

        foreach($mappings as $apiDataField => $modelField){
            $data[$modelField] = $apiData[$apiDataField];
            if(($type == 'Adjustment') && ($apiDataField == 'DocumentTypeStockChange')){
                $data[$modelField] = ($apiData[$apiDataField] == 'Positive') ? 1 : -1; 
            }
        }

        return $data;
    }

    public function createOrUpdateDocumentType($data){
        $currentModel = $this->_documentTypeFactory->create();
        $this->_documentTypeResource->load($currentModel, $data['megaventory_id'], 'megaventory_id');
        if((null !== $currentModel->getId()) && ($currentModel->getId() > 0)){
            foreach($data as $field => $value){
                $currentModel->setData($field, $value);
            }
        }
        else{
            $currentModel->setData($data);
        }

        $this->_documentTypeResource->save($currentModel);

        return ($currentModel->getMegaventoryId() !== null);
    }

    public function createOrUpdateAdjustmentTemplate($data){
        $currentModel = $this->_adjustmentTemplateFactory->create();
        $this->_adjustmentTemplateResource->load($currentModel, $data['megaventory_id'], 'megaventory_id');
        if((null !== $currentModel->getId()) && ($currentModel->getId() > 0)){
            foreach($data as $field => $value){
                $currentModel->setData($field, $value);
            }
        }
        else{
            $currentModel->setData($data);
        }

        $this->_adjustmentTemplateResource->save($currentModel);

        return ($currentModel->getMegaventoryId() !== null);
    }

    protected function deleteTypesNotExistingInMv(){
        $action = 'DocumentTypeGet';
        $apiParams = [
            'APIKEY' => $this->_mvHelper->getApiKey(),
        ];

        $mvTemplates = $this->_documentTypeCollectionFactory->create();

        foreach($mvTemplates as $documentTemplate){
            $apiParams['Filters'] = [
                [
                    'FieldName'=>'DocumentTypeID',
                    'SearchOperator'=>'Equals',
                    'SearchValue'=>$documentTemplate->getMegaventoryId()
                ]
            ];

            $result = $this->_mvHelper->makeJsonRequest($apiParams, $action);

            if(array_key_exists('mvDocumentTypes', $result) && count($result['mvDocumentTypes']) == 0){
                $this->_documentTypeResource->delete($documentTemplate);
            }
        }
    }

    protected function deleteAdjustmentsNotExistingInMv(){
        $action = 'DocumentTypeGet';
        $apiParams = [
            'APIKEY' => $this->_mvHelper->getApiKey(),
        ];

        $mvTemplates = $this->_adjustmentTemplateCollectionFactory->create();

        foreach($mvTemplates as $adjustmentTemplate){
            $apiParams['Filters'] = [
                [
                    'FieldName'=>'DocumentTypeID',
                    'SearchOperator'=>'Equals',
                    'SearchValue'=>$adjustmentTemplate->getMegaventoryId()
                ]
            ];

            $result = $this->_mvHelper->makeJsonRequest($apiParams, $action);

            if(array_key_exists('mvDocumentTypes', $result) && count($result['mvDocumentTypes']) == 0){
                $this->_adjustmentTemplateResource->delete($adjustmentTemplate);
            }
        }
    }

    public function reloadDocumentTypesFromApi($resetWebsites = false){
        $filters = $this->getFilters('SalesOrder');

        $apiParams = [
            'APIKEY' => $this->_mvHelper->getApiKey()
        ];

        if(count($filters) > 0){
            $apiParams['Filters'] = $filters;
        }
        $action = 'DocumentTypeGet';

        $data = $this->_mvHelper->makeJsonRequest($apiParams, $action);

        $errors = false;

        foreach($data['mvDocumentTypes'] as $documentType){
            $modelData = $this->convertApiDataToModelData($documentType, 'SalesOrder');
            if($resetWebsites){
                if(($modelData['shortname'] != 'SO')){
                    $modelData['magento_website_ids'] = '';
                }
                else{
                    $modelData['magento_website_ids'] = implode(',', $this->getAllWebsiteIds());
                }
            }
            $isImported = $this->createOrUpdateDocumentType($modelData);
            if(!$isImported){
                $event = [
                    'code' => $action,
                    'result' => 'order template import failed',
                    'magento_id' => '0',
                    'return_entity' => '0',
                    'details' => 'order template with abbreviation '. $data['DocumentTypeAbbreviation'] . ' has not been imported.',
                    'data' => json_encode($data)
                ];
                $this->_mvHelper->log($event);
                $errors = true;
            }
        }
        $this->deleteTypesNotExistingInMv();
        return $errors;
    }

    public function reloadAdjustmentTemplatesFromApi(){
        $filters = $this->getFilters('Adjustment');

        $apiParams = [
            'APIKEY' => $this->_mvHelper->getApiKey()
        ];

        if(count($filters) > 0){
            $apiParams['Filters'] = $filters;
        }
        $action = 'DocumentTypeGet';

        $data = $this->_mvHelper->makeJsonRequest($apiParams, $action);

        $errors = false;

        foreach($data['mvDocumentTypes'] as $documentType){
            $modelData = $this->convertApiDataToModelData($documentType, 'Adjustment');
            $isImported = $this->createOrUpdateAdjustmentTemplate($modelData);
            if(!$isImported){
                $event = [
                    'code' => $action,
                    'result' => 'adjustment template import failed',
                    'magento_id' => '0',
                    'return_entity' => '0',
                    'details' => 'adjustment template with abbreviation '. $data['DocumentTypeAbbreviation'] . ' has not been imported.',
                    'data' => json_encode($data)
                ];
                $this->_mvHelper->log($event);
                $errors = true;
            }
        }
        $this->deleteAdjustmentsNotExistingInMv();
        return $errors;
    }
}