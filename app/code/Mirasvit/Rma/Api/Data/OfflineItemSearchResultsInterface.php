<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-rma
 * @version   2.1.26
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Rma\Api\Data;

interface OfflineItemSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Mirasvit\Rma\Api\Data\OfflineItemInterface[]
     */
    public function getItems();

    /**
     * Set offline item list.
     *
     * @param array $items Array of \Mirasvit\Rma\Api\Data\OfflineItemInterface[]
     * @return $this
     */
    public function setItems(array $items);
}
