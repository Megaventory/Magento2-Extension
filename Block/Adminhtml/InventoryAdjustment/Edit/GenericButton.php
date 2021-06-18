<?php

namespace Mv\Megaventory\Block\Adminhtml\InventoryAdjustment\Edit;

use Magento\Framework\App\RequestInterface;

class GenericButton
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;


    protected $request;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        RequestInterface $requestInterface
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->request = $requestInterface;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }

    public function getId()
    {
        $id = $this->request->getParam('id',null);
        return $id;
    }
}
