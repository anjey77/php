<?php

namespace Omnyfy\VendorReview\Block\Vendor;

use Magento\Framework\View\Element\Template;

class Summary extends \Magento\Framework\View\Element\Template
{
    /**
     * Catalog vendor model
     *
     * @var \Omnyfy\Vendor\Api\VendorRepositoryInterface
     */
    protected $vendorRepository;

    public function __construct(
        \Omnyfy\Vendor\Api\VendorRepositoryInterface $vendorRepository,
        Template\Context $context,
        array $data = []
    )
    {
        $this->vendorRepository = $vendorRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get review vendor id
     *
     * @return int
     */
    protected function getVendorId()
    {
        return $this->getRequest()->getParam('id', false);
    }
    /**
     * Get vendor info
     *
     * @return Vendor
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getVendorInfo()
    {
        return $this->vendorRepository->getById(
            $this->getVendorId(),
            false,
            $this->_storeManager->getStore()->getId()
        );
    }

}
