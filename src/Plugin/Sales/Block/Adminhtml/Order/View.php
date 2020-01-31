<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Plugin\Sales\Block\Adminhtml\Order;

use Evalent\EcsterPay\Helper\Data as EcsterPayHelper;

class View
{
    protected $_helper;

    public function __construct(
        EcsterPayHelper $ecsterpayHelper
    ) {
        $this->_helper = $ecsterpayHelper;
    }

    public function afterGetCreditmemoUrl(
        \Magento\Sales\Block\Adminhtml\Order\View $subject,
        $result
    ) {
        $order = $subject->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();

        if ($this->_helper->isEnabled($order->getStoreId())
            && $method->getCode() == 'ecsterpay'
        ) {
            if ($order->canCreditmemo()) {
                return $subject->getUrl('ecsterpay/order_creditmemo/start');
            }

            return $result;
        }
    }
}