<?php

namespace Omnyfy\Vendor\Model\Indexer\Location\Flat\Plugin;

class StoreGroup
{
    /**
     * Location flat indexer processor
     *
     * @var \Omnyfy\Vendor\Model\Indexer\Location\Flat\Processor
     */
    protected $_locationFlatIndexerProcessor;

    /**
     * @param \Omnyfy\Vendor\Model\Indexer\Location\Flat\Processor $locationFlatIndexerProcessor
     */
    public function __construct(\Omnyfy\Vendor\Model\Indexer\Location\Flat\Processor $locationFlatIndexerProcessor)
    {
        $this->_locationFlatIndexerProcessor = $locationFlatIndexerProcessor;
    }

    /**
     * Before save handler
     *
     * @param \Magento\Store\Model\ResourceModel\Group $subject
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(\Magento\Store\Model\ResourceModel\Group $subject, \Magento\Framework\Model\AbstractModel $object)
    {
        if (!$object->getId() || $object->dataHasChangedFor('root_category_id')) {
            $this->_locationFlatIndexerProcessor->markIndexerAsInvalid();
        }
    }
}
