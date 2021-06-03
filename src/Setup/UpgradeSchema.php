<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        if (version_compare($context->getVersion(), '1.0.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_invoice'),
                'ecster_creditmemo_remain_fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => "12,4",
                    'nullable' => true,
                    'after' => 'ecster_debit_reference',
                    'comment' => 'Ecster Invoice Remaining Creditmemo Amount',
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable('sales_invoice'),
                'ecster_creditmemo_status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 8,
                    'nullable' => true,
                    'after' => 'ecster_creditmemo_remain_fee',
                    'comment' => 'Ecster Invoice Remaining Creditmemo Status',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $setup->getConnection()->changeColumn(
                $setup->getTable('evalent_ecsterpay_transaction_history'),
                'order_id',
                'order_id',
                ['unsigned' => true, 'nullable' => true, 'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 'comment' => 'Order Id'],
            );

            $setup->getConnection()->changeColumn(
                $setup->getTable('evalent_ecsterpay_transaction_history'),
                'entity_id',
                'entity_id',
                ['nullable' => true, 'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 'comment' => 'Entity id'],
            );
            $setup->getConnection()->changeColumn(
                $setup->getTable('evalent_ecsterpay_transaction_history'),
                'entity_type',
                'entity_type',
                ['nullable' => true, 'size' => 16, 'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'comment' => 'Entity Type'],
            );
            $setup->getConnection()->changeColumn(
                $setup->getTable('evalent_ecsterpay_transaction_history'),
                'amount',
                'amount',
                ['nullable' => true, 'size' => [12, 4], 'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, 'comment' => 'Amount'],
            );
            
            
            $setup->getConnection()->addColumn(
                $setup->getTable('evalent_ecsterpay_transaction_history'),
                'timestamp',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Timetamp of the OEN update.',
                ]
            );
        }

        $installer->endSetup();
    }
}
