<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Block\Adminhtml\Sales\Order\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Evalent\EcsterPay\Helper\Data as EcsterHelper;
use Evalent\EcsterPay\Model\TransactionHistory as TransactionHistoryModel;

class TransactionHistory extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'Evalent_EcsterPay::sales/order/tab/transaction_history.phtml';

    protected $_transactionHistory;
    protected $_pricingHelper;
    protected $_helper;
    protected $_timezone;

    public function __construct(
        Context $context,
        Registry $registry,
        TransactionHistoryModel $transactionHistory,
        PricingHelper $pricingHelper,
        EcsterHelper $helper,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_transactionHistory = $transactionHistory;
        $this->_pricingHelper = $pricingHelper;
        $this->_helper = $helper;
        $this->_timezone = $timezone;

        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /*
     * todo: phpdoc with return type
     */
    public function getPaymentMethod()
    {
        $payment = $this->getOrder()->getPayment();

        return $payment->getMethodInstance();
    }

    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    public function getTransactionHistory()
    {
        if ($this->getOrderId()) {
            return $this->_transactionHistory->loadEntity($this->getOrderId());
        }
    }

    public function getEntityType($entityType)
    {
        switch ($entityType) {
            case "order":
                return __('Ordered');
                break;
            case "invoice":
                return __('Invoiced');
                break;
            case "creditmemo":
                return __('Credited');
                break;
            case "cancel_order":
                return __('Order Canceled');
                break;
        }
    }

    public function getAmountWithFormat($amount)
    {
        return $this->_pricingHelper->currency($amount);
    }

    public function getTransactionType($transactionType)
    {

        switch ($transactionType) {
            case \Evalent\EcsterPay\Model\Api\Ecster::ECSTER_OMA_TYPE_OEN_UPDATE:
                return __('OEN Update');
                break;
            case \Evalent\EcsterPay\Model\Api\Ecster::ECSTER_OMA_TYPE_DEBIT:
                return __('Debit');
                break;
            case \Evalent\EcsterPay\Model\Api\Ecster::ECSTER_OMA_TYPE_CREDIT:
                return __('Credit');
                break;
            case \Evalent\EcsterPay\Model\Api\Ecster::ECSTER_OMA_TYPE_ANNUL:
                return __('Annul');
                break;
        }
    }

    public function getFormattedDate($date)
    {
        return $this->_timezone->formatDateTime($date);
    }

    public function getTransactionMode()
    {
        return $this->_helper->getTransactionMode($this->getOrder()->getStoreId());
    }

    public function getTabLabel()
    {
        return __('EcsterPay Transaction History');
    }

    public function getTabTitle()
    {
        return __('EcsterPay Transaction History');
    }

    public function canShowTab()
    {
        return ($this->_helper->isEnabled($this->getOrder()->getStoreId())
            && $this->getPaymentMethod()->getCode() == 'ecsterpay'
        );
    }

    public function isHidden()
    {
        return false;
    }

    public function getTabClass()
    {
        return 'ajax only';
    }

    public function getClass()
    {
        return $this->getTabClass();
    }

    public function getTabUrl()
    {
        return $this->getUrl('ecsterpay/transaction/index', ['_current' => true, 'order_id' => $this->getOrderId()]);
    }
}