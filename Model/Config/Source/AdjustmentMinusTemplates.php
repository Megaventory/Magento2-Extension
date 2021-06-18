<?php

namespace Mv\Megaventory\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AdjustmentMinusTemplates implements OptionSourceInterface{

    protected $_templateCollectionFactory;

    public function __construct(
        \Mv\Megaventory\Model\ResourceModel\AdjustmentTemplate\CollectionFactory $adjustmentTemplateCollectionFactory
    )
    {
        $this->_templateCollectionFactory = $adjustmentTemplateCollectionFactory;
    }

    public function toOptionArray()
    {
        $templates = $this->_templateCollectionFactory->create()->addFieldToFilter('stock_change',-1);
        $options = [];

        foreach($templates as $template){
            $options[] = ['label'=>$template->getName(),'value'=>$template->getId()];
        }

        return $options;
    }
}