<?php

namespace Mv\Megaventory\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

class Websites implements OptionSourceInterface{

    protected $_storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    )
    {
        $this->_storeManager = $storeManager;        
    }

    public function toOptionArray()
    {
        $websites = $this->_storeManager->getWebsites(true);

        $options = [];

        foreach($websites as $website){
            if($website->getCode() != 'admin'){
                $options[] = ['label'=>$website->getName(),'value'=>$website->getId()];
            }
        }

        return $options;
    }
}