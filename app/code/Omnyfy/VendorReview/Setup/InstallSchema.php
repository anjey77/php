<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Omnyfy\VendorReview\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        /**
         * Create table 'omnyfy_vendor_review_entity'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('omnyfy_vendor_review_entity'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Review entity id'
            )
            ->addColumn(
                'entity_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                ['nullable' => false],
                'Review entity code'
            )
            ->setComment('Review entities');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'omnyfy_vendor_review_status'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('omnyfy_vendor_review_status'))
            ->addColumn(
                'status_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Status id'
            )
            ->addColumn(
                'status_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                ['nullable' => false],
                'Status code'
            )
            ->setComment('Review statuses');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vendor_review'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vendor_review'))
            ->addColumn(
                'omnyfy_vendor_review_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Review id'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Review create date'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity id'
            )
            ->addColumn(
                'entity_pk_value',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Vendor id'
            )
            ->addColumn(
                'status_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Status code'
            )
            ->addIndex(
                $installer->getIdxName('vendor_review', ['entity_id']),
                ['entity_id']
            )
            ->addIndex(
                $installer->getIdxName('vendor_review', ['status_id']),
                ['status_id']
            )
            ->addIndex(
                $installer->getIdxName('vendor_review', ['entity_pk_value']),
                ['entity_pk_value']
            )
            ->addForeignKey(
                $installer->getFkName('vendor_review', 'entity_id', 'omnyfy_vendor_review_entity', 'entity_id'),
                'entity_id',
                $installer->getTable('omnyfy_vendor_review_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('vendor_review', 'status_id', 'omnyfy_vendor_review_status', 'status_id'),
                'status_id',
                $installer->getTable('omnyfy_vendor_review_status'),
                'status_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_NO_ACTION
            )
            ->setComment('Review base information');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'omnyfy_vendor_review_detail'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('omnyfy_vendor_review_detail'))
            ->addColumn(
                'detail_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Review detail id'
            )
            ->addColumn(
                'omnyfy_vendor_review_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Review id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'default' => '0'],
                'Store id'
            )
            ->addColumn(
                'title',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Title'
            )
            ->addColumn(
                'detail',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                ['nullable' => false],
                'Detail description'
            )
            ->addColumn(
                'nickname',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                128,
                ['nullable' => false],
                'User nickname'
            )
            ->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Customer Id'
            )
            ->addIndex(
                $installer->getIdxName('omnyfy_vendor_review_detail', ['omnyfy_vendor_review_id']),
                ['omnyfy_vendor_review_id']
            )
            ->addIndex(
                $installer->getIdxName('omnyfy_vendor_review_detail', ['store_id']),
                ['store_id']
            )
            ->addIndex(
                $installer->getIdxName('omnyfy_vendor_review_detail', ['customer_id']),
                ['customer_id']
            )
            ->addForeignKey(
                $installer->getFkName('omnyfy_vendor_review_detail', 'customer_id', 'customer_entity', 'entity_id'),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $installer->getFkName('omnyfy_vendor_review_detail', 'omnyfy_vendor_review_id', 'vendor_review', 'omnyfy_vendor_review_id'),
                'omnyfy_vendor_review_id',
                $installer->getTable('vendor_review'),
                'omnyfy_vendor_review_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('omnyfy_vendor_review_detail', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->setComment('Review detail information');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'omnyfy_vendor_review_entity_summary'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('omnyfy_vendor_review_entity_summary'))
            ->addColumn(
                'primary_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Summary review entity id'
            )
            ->addColumn(
                'entity_pk_value',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Vendor id'
            )
            ->addColumn(
                'entity_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Entity type id'
            )
            ->addColumn(
                'reviews_count',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Qty of reviews'
            )
            ->addColumn(
                'vendor_rating_summary',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Summarized rating'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store id'
            )
            ->addIndex(
                $installer->getIdxName('omnyfy_vendor_review_entity_summary', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $installer->getFkName('omnyfy_vendor_review_entity_summary', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Review aggregates');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'omnyfy_vendor_review_store'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('omnyfy_vendor_review_store'))
            ->addColumn(
                'omnyfy_vendor_review_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Review Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store Id'
            )
            ->addIndex(
                $installer->getIdxName('omnyfy_vendor_review_store', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $installer->getFkName('omnyfy_vendor_review_store', 'omnyfy_vendor_review_id', 'vendor_review', 'omnyfy_vendor_review_id'),
                'omnyfy_vendor_review_id',
                $installer->getTable('vendor_review'),
                'omnyfy_vendor_review_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('omnyfy_vendor_review_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Review Store');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vendor_rating_entity'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vendor_rating_entity'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            )
            ->addColumn(
                'entity_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                64,
                ['nullable' => false],
                'Entity Code'
            )
            ->addIndex(
                $installer->getIdxName(
                    'vendor_rating_entity',
                    ['entity_code'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_code'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setComment('Rating entities');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vendor_rating'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vendor_rating'))
            ->addColumn(
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rating Id'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Entity Id'
            )
            ->addColumn(
                'vendor_rating_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                64,
                ['nullable' => false],
                'Rating Code'
            )
            ->addColumn(
                'position',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Rating Position On Storefront'
            )
            ->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 1],
                'Rating is active.'
            )
            ->addIndex(
                $installer->getIdxName(
                    'vendor_rating',
                    ['vendor_rating_code'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['vendor_rating_code'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $installer->getIdxName('vendor_rating', ['entity_id']),
                ['entity_id']
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating', 'entity_id', 'vendor_rating_entity', 'entity_id'),
                'entity_id',
                $installer->getTable('vendor_rating_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Ratings');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vendor_rating_option'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vendor_rating_option'))
            ->addColumn(
                'option_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Rating Option Id'
            )
            ->addColumn(
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Rating Id'
            )
            ->addColumn(
                'code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                ['nullable' => false],
                'Rating Option Code'
            )
            ->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Rating Option Value'
            )
            ->addColumn(
                'position',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Ration option position on Storefront'
            )
            ->addIndex(
                $installer->getIdxName('vendor_rating_option', ['vendor_rating_id']),
                ['vendor_rating_id']
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_option', 'vendor_rating_id', 'vendor_rating', 'vendor_rating_id'),
                'vendor_rating_id',
                $installer->getTable('vendor_rating'),
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Rating options');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vendor_rating_option_vote'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vendor_rating_option_vote'))
            ->addColumn(
                'vote_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Vote id'
            )
            ->addColumn(
                'option_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Vote option id'
            )
            ->addColumn(
                'remote_ip',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                16,
                ['nullable' => false],
                'Customer IP'
            )
            ->addColumn(
                'remote_ip_long',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['nullable' => false, 'default' => 0],
                'Customer IP converted to long integer format'
            )
            ->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'default' => 0],
                'Customer Id'
            )
            ->addColumn(
                'entity_pk_value',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Vendor id'
            )
            ->addColumn(
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Rating id'
            )
            ->addColumn(
                'omnyfy_vendor_review_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['unsigned' => true],
                'Review id'
            )
            ->addColumn(
                'percent',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0],
                'Percent amount'
            )
            ->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0],
                'Vote option value'
            )
            ->addIndex(
                $installer->getIdxName('vendor_rating_option_vote', ['option_id']),
                ['option_id']
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_option_vote', 'option_id', 'vendor_rating_option', 'option_id'),
                'option_id',
                $installer->getTable('vendor_rating_option'),
                'option_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_option_vote', 'omnyfy_vendor_review_id', 'vendor_review', 'omnyfy_vendor_review_id'),
                'omnyfy_vendor_review_id',
                $installer->getTable('vendor_review'),
                'omnyfy_vendor_review_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Rating option values');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vendor_rating_option_vote_aggregated'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vendor_rating_option_vote_aggregated'))
            ->addColumn(
                'primary_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Vote aggregation id'
            )
            ->addColumn(
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Rating id'
            )
            ->addColumn(
                'entity_pk_value',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Vendor id'
            )
            ->addColumn(
                'vote_count',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Vote dty'
            )
            ->addColumn(
                'vote_value_sum',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'General vote sum'
            )
            ->addColumn(
                'percent',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0],
                'Vote percent'
            )
            ->addColumn(
                'percent_approved',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['default' => '0'],
                'Vote percent approved by admin'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Store Id'
            )
            ->addIndex(
                $installer->getIdxName('vendor_rating_option_vote_aggregated', ['vendor_rating_id']),
                ['vendor_rating_id']
            )
            ->addIndex(
                $installer->getIdxName('vendor_rating_option_vote_aggregated', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_option_vote_aggregated', 'vendor_rating_id', 'vendor_rating', 'vendor_rating_id'),
                'vendor_rating_id',
                $installer->getTable('vendor_rating'),
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_option_vote_aggregated', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Rating vote aggregated');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vendor_rating_store'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vendor_rating_store'))
            ->addColumn(
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => true],
                'Rating id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => true],
                'Store id'
            )
            ->addIndex(
                $installer->getIdxName('vendor_rating_store', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_store', 'vendor_rating_id', 'vendor_rating', 'vendor_rating_id'),
                'vendor_rating_id',
                $installer->getTable('vendor_rating'),
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Rating Store');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'vendor_rating_title'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('vendor_rating_title'))
            ->addColumn(
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => true],
                'Rating Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0, 'primary' => true],
                'Store Id'
            )
            ->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Rating Label'
            )
            ->addIndex(
                $installer->getIdxName('vendor_rating_title', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_title', 'vendor_rating_id', 'vendor_rating', 'vendor_rating_id'),
                'vendor_rating_id',
                $installer->getTable('vendor_rating'),
                'vendor_rating_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('vendor_rating_title', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Rating Title');
        $installer->getConnection()->createTable($table);

        $setup->endSetup();

    }
}
