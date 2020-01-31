<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use \Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;

class Terms extends Template
{
    protected $_storeManager;
    protected $_layout;
    protected $_helper;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        LayoutInterface $layout,
        EcsterPayHelper $helper,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_layout = $layout;
        $this->_helper = $helper;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Ecster Checkout / Shop Terms'));
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getContent()
    {
        if ($blockId = $this->_helper->getTermsBlockId($this->getStoreId())) {
            return $this->_layout->createBlock(\Magento\Cms\Block\Block::class)
                ->setBlockId($blockId)->toHtml();
        }
    }
}
