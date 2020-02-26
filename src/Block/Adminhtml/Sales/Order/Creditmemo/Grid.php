<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Model\PageLayout\Config\BuilderInterface;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory;

class Grid extends Extended
{
    protected $request;
    protected $_collectionFactory;
    protected $_pageLayoutBuilder;

    public function __construct(
        Context $context,
        RequestInterface $request,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        BuilderInterface $pageLayoutBuilder,
        array $data = []
    ) {
        $this->request = $request;
        $this->_collectionFactory = $collectionFactory;
        $this->_pageLayoutBuilder = $pageLayoutBuilder;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('InvoiceGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        $collection->addFieldToFilter('main_table.ecster_creditmemo_status', ['in' => ['new', 'partial']]);

        if ($orderId = $this->request->getParam('order_id')) {
            $collection->addFieldToFilter('main_table.order_id', $orderId);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn(
            "increment_id",
            [
                "header" => __("Invoice"),
                "index" => "increment_id",
            ]
        );

        $this->addColumn(
            "created_at",
            [
                "header" => __("Invoice Date"),
                "index" => "created_at",
                "type" => "datetime"
            ]
        );

        $this->addColumn(
            "grand_total",
            [
                "header" => __("Amount"),
                "index" => "grand_total",
                "type" => "currency",
                "currency" => "order_currency_code"
            ]
        );

        $this->addColumn(
            "ecster_creditmemo_remain_fee",
            [
                "header" => __("Credit Memo Remaining Amount"),
                "index" => "ecster_creditmemo_remain_fee",
                "type" => "currency",
                "currency" => "order_currency_code"
            ]
        );

        $this->addColumn(
            "ecster_creditmemo_status",
            [
                "header" => __("Credit Memo Status"),
                "index" => "ecster_creditmemo_status",
            ]
        );

        $this->addColumn(
            "action",
            [
                "header" => __("Action"),
                "renderer" => "Evalent\EcsterPay\Block\Adminhtml\Sales\Order\Creditmemo\Renderer\Action"
            ]
        );

        parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('sales/order_creditmemo/new',
            ['order_id' => $row->getOrderId(), 'invoice_id' => $row->getId()]);
    }
}