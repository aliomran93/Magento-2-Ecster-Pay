<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\ResourceModel\TransactionHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Evalent\EcsterPay\Model\TransactionHistory::class,
            \Evalent\EcsterPay\Model\ResourceModel\TransactionHistory::class
        );
    }
}