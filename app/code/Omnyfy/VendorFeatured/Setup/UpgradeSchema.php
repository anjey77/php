<?php


namespace Omnyfy\VendorFeatured\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        if (version_compare($context->getVersion(), "1.0.1", "<")) {
            $enquiryTable = $setup->getTable('omnyfy_vendorfeatured_vendor_featured');
            if ($setup->tableExists($enquiryTable) && !$connection->tableColumnExists($enquiryTable, 'location_id')) {
                $connection->addColumn(
                    $enquiryTable,
                    'location_id',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'nullable' => true,
                        'comment' => 'Location ID',
                        'unsigned' => true,
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), "1.0.4", "<")) {
            $table = $setup->getTable('omnyfy_vendorfeatured_promo_widget');
            if (!$setup->tableExists($table)) {
                $promotable = $connection->newTable($table)
                    ->addColumn(
                            'entity_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                            'Entity ID'
                    )->addColumn(
                            'description',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => true],
                            'Description'
                    )->addColumn(
                            'link_label_1',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => false],
                            'Link Label 1'
                    )->addColumn(
                            'link_url_1',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => false],
                            'Link URL 1'
                    )->addColumn(
                            'link_label_2',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => false],
                            'Link Label 2'
                    )->addColumn(
                            'link_url_2',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => false],
                            'Link URL 2'
                    )->addColumn(
                            'background_colour',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => false],
                            'Background Colour'
                    )->addColumn(
                            'text_colour',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => false],
                            'Text Colour'
                    )->addColumn(
                            'vendor_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['unsigned' => true, 'nullable' => false],
                            'Vendor ID'
                    )->addColumn(
                            'created_at',
                            Table::TYPE_TIMESTAMP,
                            null,
                            ['nullable' => true, 'default' => null],
                            'Created At'
                    )
                    ->setComment(
                    'Promo Vendor Widget Table'
                );
                $connection->createTable($promotable);
            }
        }

        if (version_compare($context->getVersion(), "1.0.5", "<")) {
            $promoTable = $setup->getTable('omnyfy_vendorfeatured_promo_widget');
            if ($setup->tableExists($promoTable) && !$connection->tableColumnExists($promoTable, 'sort_order')) {
                $connection->addColumn(
                    $promoTable,
                    'sort_order',
                    [
                        'type'     => Table::TYPE_INTEGER,
                        'nullable' => false,
                        'comment'  => 'Sort Order',
                        'unsigned' => true,
                        'default'  => 999,
                        'after'    => 'vendor_id',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), "1.0.6", "<")) {
            $table = $setup->getTable('omnyfy_spotlight_banner_placement');
            if (!$setup->tableExists($table)) {
                $placementtable = $connection->newTable($table)
                    ->addColumn(
                            'banner_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                            'Banner ID'
                    )->addColumn(
                            'banner_name',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => false],
                            'Banner Name'
                    )->addColumn(
                            'category_ids',
                            Table::TYPE_TEXT,
                            '2K',
                            ['nullable' => true],
                            'Category Ids'
                    )->addColumn(
                            'vendor_ids',
                            Table::TYPE_TEXT,
                            '2K',
                            ['nullable' => true],
                            'Vendor Ids'
                    )->addColumn(
                            'created_at',
                            Table::TYPE_TIMESTAMP,
                            null,
                            ['nullable' => true, 'default' => null],
                            'Created At'
                    )
                    ->setComment(
                    'Vendor Spotlight Banner Placement Table'
                );
                $connection->createTable($placementtable);
            }

            $table = $setup->getTable('omnyfy_spotlight_banner_vendor');
            if (!$setup->tableExists($table)) {
                $bannervendortable = $connection->newTable($table)
                    ->addColumn(
                            'banner_vendor_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                            'Banner Vendor ID'
                    )->addColumn(
                            'banner_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['unsigned' => true, 'nullable' => false],
                            'Banner ID'
                    )->addColumn(
                            'vendor_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['unsigned' => true, 'nullable' => false],
                            'Vendor ID'
                    )->addColumn(
                            'sort_order',
                            Table::TYPE_INTEGER,
                            null,
                            ['unsigned' => true, 'nullable' => false],
                            'Sort Order'
                    )
                    ->setComment(
                    'Vendor Spotlight Banner Vendor Table'
                );
                $connection->createTable($bannervendortable);
            }

            $table = $setup->getTable('omnyfy_spotlight_clicks');
            if (!$setup->tableExists($table)) {
                $clickstable = $connection->newTable($table)
                    ->addColumn(
                            'click_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                            'Click ID'
                    )->addColumn(
                            'banner_vendor_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['unsigned' => true, 'nullable' => false],
                            'Banner Vendor ID'
                    )->addColumn(
                            'created_at',
                            Table::TYPE_TIMESTAMP,
                            null,
                            ['nullable' => true, 'default' => null],
                            'Created At'
                    )
                    ->setComment(
                    'Vendor Spotlight Clicks Table'
                );
                $connection->createTable($clickstable);
            }
        }

        if (version_compare($context->getVersion(), "1.0.7", "<")) {
            $enquiryTable = $setup->getTable('omnyfy_vendorfeatured_vendor_featured');
            if ($setup->tableExists($enquiryTable) && $connection->tableColumnExists($enquiryTable, 'added_date')) {
                $connection->changeColumn(
                    $enquiryTable,
                    'added_date',
                    'added_date',
                    [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => false,
                        'comment' => 'Added Date'
                    ]
                );
            }
        }
    }
}
