<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Groupcat
 */


namespace Amasty\Groupcat\Plugin\Indexer;

use Amasty\Groupcat\Model\Indexer\Rule\RuleProductProcessor;

class CustomerGroup extends \Magento\CatalogRule\Plugin\Indexer\CustomerGroup
{
    /**
     * override constructor. Indexer is changed
     *
     * @param RuleProductProcessor $ruleProductProcessor
     */
    public function __construct(RuleProductProcessor $ruleProductProcessor)
    {
        $this->ruleProductProcessor = $ruleProductProcessor;
    }
}
