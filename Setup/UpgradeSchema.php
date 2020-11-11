<?php

namespace SantanderPaymentSolutions\SantanderPayments\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $table = $setup->getConnection()->newTable($setup->getTable('santander_transactions'));

        $table
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'primary'  => true,
                    'nullable' => false
                ]
            )
            ->addColumn(
                'orderId',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ]
            )
            ->addColumn(
                'customerId',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ]
            )
            ->addColumn(
                'paymentId',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ]
            )
            ->addColumn(
                'method',
                Table::TYPE_TEXT,
                32,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                32,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                32,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'uniqueId',
                Table::TYPE_TEXT,
                128,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'reference',
                Table::TYPE_TEXT,
                128,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'createDatetime',
                Table::TYPE_DATETIME,
                null,
                [
                    'nullable' => false
                ]
            )
            ->addColumn(
                'amount',
                Table::TYPE_DECIMAL,
                '8,2',
                [
                    'nullable' => false
                ]
            )
            ->addColumn(
                'currency',
                Table::TYPE_TEXT,
                8,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'sessionId',
                Table::TYPE_TEXT,
                128,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'request',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'response',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => true
                ]
            );
        $setup->getConnection()->createTable($table);

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('santander_transactions'),
                'transaction_comment',
                [
                    'type'     => Table::TYPE_TEXT,
                    'size'     => 200,
                    'nullable' => true,
                    'comment'  => 'Additional transaction comment'
                ]
            );
        }

        $setup->endSetup();

    }

}
