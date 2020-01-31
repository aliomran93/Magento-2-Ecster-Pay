<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TransactionHistory extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('evalent_ecsterpay_transaction_history', 'id');
    }
}