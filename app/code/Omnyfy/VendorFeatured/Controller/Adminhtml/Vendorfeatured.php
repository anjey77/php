<?php


namespace Omnyfy\VendorFeatured\Controller\Adminhtml;

abstract class Vendorfeatured extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Omnyfy_VendorFeatured::top_level';
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Omnyfy'), __('Omnyfy'))
            ->addBreadcrumb(__('Vendor Featured'), __('Vendor Featured'));
        return $resultPage;
    }
}
