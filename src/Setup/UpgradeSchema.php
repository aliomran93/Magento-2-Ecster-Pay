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

        $installer->endSetup();
    }
}