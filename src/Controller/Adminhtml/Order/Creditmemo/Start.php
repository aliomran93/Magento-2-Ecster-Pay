<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Adminhtml\Order\Creditmemo;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Start extends \Magento\Backend\App\Action
{
    protected $_resultPageFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory

    ) {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    const ADMIN_RESOURCE = 'Magento_Sales::sales_creditmemo';

    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();

        return $resultPage;
    }
}
