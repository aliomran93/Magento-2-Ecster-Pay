<?php
/**
 * Copyright © Evalent Group AB, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Evalent\EcsterPay\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;

class InstallSchema implements InstallSchemaInterface
{

    private $_eavSetupFactory;

    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $columns = [
            "ecster_cart_key" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                "length" => 255,
                "nullable" => true,
                "comment" => "Evalent EcsterPay Cart Key",
            ],
            "ecster_internal_reference" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                "length" => 255,
                "nullable" => true,
                "comment" => "Evalent EcsterPay Internal Order Ref",
            ],
            "ecster_payment_type" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                "length" => 64,
                "nullable" => true,
                "comment" => "Evalent EcsterPay Payment Type",
            ],
            "ecster_extra_fee" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                "length" => "12,4",
                "nullable" => true,
                "comment" => "Evalent EcsterPay Extra Fee",
            ],
            "ecster_properties" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                "length" => \Magento\Framework\DB\Ddl\Table::MAX_TEXT_SIZE,
                "nullable" => true,
                "comment" => "Evalent EcsterPay Properties",
            ]
        ];

        $quoteTable = $setup->getTable('quote');

        $connection = $setup->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($quoteTable, $name, $definition);
        }

        $orderTable = $setup->getTable('sales_order');

        $connection = $setup->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($orderTable, $name, $definition);
        }

        $columns = [
            "national_id" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                "length" => 255,
                "nullable" => true,
                "comment" => "Evalent EcsterPay National Id",
            ]
        ];

        $quoteAddressTable = $setup->getTable('quote_address');

        $connection = $setup->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($quoteAddressTable, $name, $definition);
        }

        $orderAddressTable = $setup->getTable('sales_order_address');

        $connection = $setup->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($orderAddressTable, $name, $definition);
        }

        $columns = [
            "ecster_internal_reference" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                "length" => 255,
                "nullable" => true,
                "comment" => "Evalent EcsterPay Internal Order Ref",
            ],
            "ecster_extra_fee" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                "length" => "12,4",
                "nullable" => true,
                "comment" => "Evalent EcsterPay Extra Fee",
            ],
            "ecster_debit_reference" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                "length" => \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                "nullable" => true,
                "comment" => "Evalent EcsterPay Debit Transaction Id",
            ]
        ];

        $invoiceTable = $setup->getTable('sales_invoice');

        $connection = $setup->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($invoiceTable, $name, $definition);
        }

        $columns = [
            "ecster_extra_invoice_remain_fee" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                "length" => "12,4",
                "nullable" => true,
                "default" => 0,
                "comment" => "Evalent EcsterPay Extra Invoice Remain_Fee",
            ],
            "ecster_extra_creditmemo_remain_fee" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                "length" => "12,4",
                "nullable" => true,
                "default" => 0,
                "comment" => "Evalent EcsterPay Extra Creditmemo Remain_Fee",
            ]
        ];

        $orderTable = $setup->getTable('sales_order');

        $connection = $setup->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($orderTable, $name, $definition);
        }

        $columns = [
            "ecster_extra_fee" => [
                "type" => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                "length" => "12,4",
                "nullable" => true,
                "comment" => "Evalent EcsterPay Extra Fee",
            ]
        ];

        $creditmemoTable = $setup->getTable('sales_creditmemo');

        $connection = $setup->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($creditmemoTable, $name, $definition);
        }

        $table = $setup->getConnection()->newTable(
            $setup->getTable('evalent_ecsterpay_transaction_history')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Primary key'
        )->addColumn(
            'entity_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            16,
            ['nullable' => false],
            'Entity Type'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            ['unsigned' => true, 'nullable' => false],
            'Order Id'
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            ['nullable' => false],
            'Entity Id'
        )->addColumn(
            'amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            [12, 4],
            ['nullable' => false],
            'Amount'
        )->addColumn(
            'transaction_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            ['nullable' => false],
            'Transaction Type'
        )->addColumn(
            'request_params',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            \Magento\Framework\DB\Ddl\Table::MAX_TEXT_SIZE,
            ['nullable' => true],
            'Request Params'
        )->addColumn(
            'transaction_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            256,
            ['nullable' => true],
            'Transaction ıd'
        )->addColumn(
            'order_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            256,
            ['nullable' => true],
            'Order Status'
        )->addColumn(
            'response_params',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            \Magento\Framework\DB\Ddl\Table::MAX_TEXT_SIZE,
            ['nullable' => true],
            'Respnse Params'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false],
            'Created Date'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false],
            'Updated Date'
        )->addIndex(
            $setup->getIdxName('evalent_ecsterpay_transaction_history', ['order_id']),
            ['order_id']
        )->addForeignKey(
            $setup->getFkName('evalent_ecsterpay_transaction_history', 'order_id', 'sales_order', 'entity_id'),
            'order_id',
            $setup->getTable('sales_order'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Evalent EcsterPay Transaction History'
        );

        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}