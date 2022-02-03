<?php

namespace Omnyfy\VendorReview\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->deleteUnrelatedData($setup);
            $this->initOverallRating($setup);
        }
    }


    public function initOverallRating(ModuleDataSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $data = [
            \Omnyfy\VendorReview\Model\Rating::ENTITY_PRODUCT_CODE => [
                ['vendor_rating_code' => 'Overall', 'position' => 0]
            ]
        ];

        foreach ($data as $entityCode => $ratings) {
            //Fill table rating/vendor_rating_entity
            $query = $connection->select()->from($connection->getTableName('vendor_rating_entity'), ['entity_id'])->where('entity_code = ? ', 'vendor');
            $entityId = $connection->fetchOne($query);

            foreach ($ratings as $bind) {
                //Fill table rating/rating
                $bind['entity_id'] = $entityId;
                $connection->insert($setup->getTable('vendor_rating'), $bind);

                //Fill table rating/vendor_rating_option
                $ratingId = $setup->getConnection()->lastInsertId($setup->getTable('vendor_rating'));

                $connection->insertOnDuplicate($setup->getTable('vendor_rating_store'), ['vendor_rating_id' => $ratingId, 'store_id' => 0]);
                $connection->insertOnDuplicate($setup->getTable('vendor_rating_store'), ['vendor_rating_id' => $ratingId, 'store_id' => 1]);
                $optionData = [];
                for ($i = 1; $i <= 5; $i++) {
                    $optionData[] = ['vendor_rating_id' => $ratingId, 'code' => (string)$i, 'value' => $i, 'position' => $i];
                }
                $setup->getConnection()->insertMultiple($setup->getTable('vendor_rating_option'), $optionData);
            }
        }
    }

    public function deleteUnrelatedData(ModuleDataSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $vendorRatingTable = $setup->getTable('vendor_rating');
        $connection->delete($vendorRatingTable, ['vendor_rating_code = ?' => 'Quality']);
        $connection->delete($vendorRatingTable, ['vendor_rating_code = ?' => 'Value']);
        $connection->delete($vendorRatingTable, ['vendor_rating_code = ?' => 'Price']);
    }
}