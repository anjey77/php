<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Omnyfy\VendorReview\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Admin ratings controller
 */
abstract class Rating extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Omnyfy_VendorReview::vendor_ratings';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        SerializerInterface $serializer
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    protected function initEnityId()
    {
        $this->coreRegistry->register(
            'entityId',
            $this->_objectManager->create('Omnyfy\VendorReview\Model\Rating\Entity')->getIdByCode('vendor')
        );
    }
}
