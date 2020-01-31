<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Evalent\EcsterPay\Model\ResourceModel\TransactionHistory\CollectionFactory;

class TransactionHistory extends AbstractModel
{
    /** @var \Evalent\EcsterPay\Model\ResourceModel\TransactionHistory\CollectionFactory */
    private $_transactionHistoryCollectionFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $transactionHistoryCollectionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_transactionHistoryCollectionFactory = $transactionHistoryCollectionFactory;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    protected function _construct()
    {
        $this->_init(\Evalent\EcsterPay\Model\ResourceModel\TransactionHistory::class);
    }

    public function loadEntity($entityId, $entityType = 'order')
    {
        $transactionHistoryCollection = $this->_transactionHistoryCollectionFactory->create();
        if ($entityType == 'order') {
            $transactionHistoryCollection->addFieldToFilter('order_id', $entityId);
        } else {
            $transactionHistoryCollection->addFieldToFilter('entity_type', $entityType)
                ->addFieldToFilter('entity_id', $entityId);
        }

        $transactionHistoryCollection->setOrder('created_at', 'desc');

        return $transactionHistoryCollection;
    }
}