<?php

namespace Mv\Megaventory\Ui\Component\Listing\Columns;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class DocumentTypeWebsite extends Column{

    protected $_websiteRepository;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        WebsiteRepositoryInterface $websiteRepository,
        array $components = [],
        array $data = [] 
    )
    {
        $this->_websiteRepository = $websiteRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['id'])) {
                    $websites = explode(',', $item[$name]);
                    $content = '';
                    if((count($websites) > 0) && (!empty($item[$name]))){
                        foreach($websites as $website){
                            try{
                                $content .= $this->_websiteRepository->getById($website)->getName() . '<br/>';
                            }
                            catch(NoSuchEntityException $e){
                                // Empty handler to prevent a crash if a website is unable to be found on DB.
                            }
                        }
                        $content = rtrim($content, ' ,');
                    }
                    
                    $item[$name] = $content;
                }
            }
        }

        return $dataSource;
    }
}