<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\System\Config\Source\Order;

use Magento\Sales\Model\Config\Source\Order\Status as OrderStatus;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\Order;

class Status extends OrderStatus
{
    public function __construct(Config $orderConfig)
    {
        $this->_orderConfig = $orderConfig;
        $this->_stateStatuses[] = Order::STATE_PENDING_PAYMENT;
        $this->_stateStatuses[] = Order::STATE_PAYMENT_REVIEW;

        return $this;
    }
}
