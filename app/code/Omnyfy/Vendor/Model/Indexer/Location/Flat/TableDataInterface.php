<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Omnyfy\Vendor\Model\Indexer\Location\Flat;

/**
 * Interface TableDataInterface
 */
interface TableDataInterface
{
    /**
     * Move data from temporary tables to flat
     *
     * @param string $flatTable
     * @param string $flatDropName
     * @param string $temporaryFlatTableName
     * @return void
     */
    public function move($flatTable, $flatDropName, $temporaryFlatTableName);
}
