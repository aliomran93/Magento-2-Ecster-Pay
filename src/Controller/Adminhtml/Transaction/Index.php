<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Controller\Adminhtml\Transaction;

use Evalent\EcsterPay\Block\Adminhtml\Sales\Order\Tab\TransactionHistory;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class Index extends \Magento\Sales\Controller\Adminhtml\Order
{
    protected $layoutFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        $this->layoutFactory = $layoutFactory;
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
    }

    public function execute()
    {
        $this->_initOrder();
        $layout = $this->layoutFactory->create();
        $html = $layout->createBlock(TransactionHistory::class)->toHtml();
        $this->_translateInline->processResponseBody($html);
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($html);

        return $resultRaw;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Evalent_EcsterPay::transactions');
    }
}