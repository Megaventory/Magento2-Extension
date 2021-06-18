<?php

namespace Mv\Megaventory\Ui\Component\Listing\Columns;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\Component\Form\Fieldset;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Mv\Megaventory\Model\DocumentTypeFactory;
use Mv\Megaventory\Model\ResourceModel\DocumentType;

class DocumentTypeFieldset extends Fieldset{
    protected $_documentTypeFactory;
    protected $_documentTypeResource;
    protected $_request;

    public function __construct(
        ContextInterface $context,
        array $components = [],
        array $data = [],
        DocumentTypeFactory $documentTypeFactory,
        DocumentType $documentTypeResource,
        RequestInterface $requestInterface
    ) {
        parent::__construct($context, $components, $data);
        $this->_documentTypeFactory = $documentTypeFactory;
        $this->_documentTypeResource = $documentTypeResource;
        $this->_request = $requestInterface;
    }
    public function prepare()
    {
        $id = (int)$this->_request->getParam('id',-1);
        if($id > 0){
            $documentType = $this->_documentTypeFactory->create();
            $this->_documentTypeResource->load($documentType, $id);

            $config = $this->getData('config');

            $config['label'] = $documentType->getName() . ' (' . $documentType->getShortname() . ')';

            $this->setData('config',$config);
        }

        parent::prepare();
    }
}