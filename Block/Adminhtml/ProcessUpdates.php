<?php

namespace Mv\Megaventory\Block\Adminhtml;

class ProcessUpdates extends \Magento\Backend\Block\Template{
    protected $_template = 'pull_updates.phtml';
    public function getUpdateUrl(){
        return $this->getUrl('megaventory/updates/process');
    }
}