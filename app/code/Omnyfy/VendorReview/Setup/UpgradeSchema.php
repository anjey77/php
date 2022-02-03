<?php

namespace Omnyfy\VendorReview\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
        $installer = $setup;

        $installer->startSetup();

        if(version_compare($context->getVersion(), '2.0.2', '<')) {
            if ($installer->getConnection()->tableColumnExists('vendor_rating', 'vendor_rating_codes') === false) {
                $installer->getConnection()->addColumn(
                    $installer->getTable( 'vendor_rating' ),
                    'vendor_rating_codes',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Rating Code Store'
                    ]
                );
            }
        }

        $installer->endSetup();
    }
}