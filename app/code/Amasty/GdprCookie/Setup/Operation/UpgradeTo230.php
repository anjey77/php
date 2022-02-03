<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_GdprCookie
 */


declare(strict_types=1);

namespace Amasty\GdprCookie\Setup\Operation;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeTo230
{
    /**
     * @param SchemaSetupInterface $setup
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $cookieStoreTable = $setup->getTable(CreateCookieStoreTable::TABLE_NAME);
        $setup->getConnection()->addColumn(
            $cookieStoreTable,
            'cookie_lifetime',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Cookie Lifetime'
            ]
        );
    }
}
