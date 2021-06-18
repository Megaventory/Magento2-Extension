<?php
namespace Mv\Megaventory\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class CheckHeartBeatExpiration extends \Magento\Backend\App\Action
{
    protected $scopeConfig;
    protected $jsonResultFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        JsonFactory $jsonResultFactory,
        Context $context
    )
    {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $currentDateTime = date('Y-m-d H:i:s');
        $lastHeartBeat = $this->scopeConfig->getValue('megaventory/synchronization/starttime');
        if(empty($lastHeartBeat)){
            $result = [
                'success'=>true,
                'status'=>'lock_expired'
            ];

            $jsonResponse = $this->jsonResultFactory->create();
            $jsonResponse->setHttpResponseCode(200);
            $jsonResponse->setData($result);

            return $jsonResponse;

        }
        $expiration = date('Y-m-d H:i:s',strtotime($lastHeartBeat . ' +3 minutes'));

        $result = ['success'=>true];
        if($currentDateTime > $expiration){
            $result['status'] = 'lock_expired';
        }
        else{
            $diff = date_diff(date_create($expiration),date_create($currentDateTime));
            $representation = $diff->format('%I:%S');
            $result['status'] = 'lock_valid';
            $result['remaining'] = $representation;
        }

        $jsonResponse = $this->jsonResultFactory->create();
        $jsonResponse->setHttpResponseCode(200);
        $jsonResponse->setData($result);

        return $jsonResponse;
    }
}