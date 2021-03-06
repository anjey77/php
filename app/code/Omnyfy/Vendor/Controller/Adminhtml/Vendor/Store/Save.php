<?php
/**
 * Project: Multi Vendor
 * User: jing
 * Date: 2019-05-24
 * Time: 11:29
 */
namespace Omnyfy\Vendor\Controller\Adminhtml\Vendor\Store;

use Magento\Framework\Stdlib\DateTime\Filter\DateTime;

class Save extends \Omnyfy\Vendor\Controller\Adminhtml\AbstractAction
{
    const ADMIN_RESOURCE = 'Omnyfy_Vendor::vendor_stores';

    protected $vendorFactory;

    private $dateTimeFilter;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Psr\Log\LoggerInterface $logger,
        \Omnyfy\Vendor\Model\VendorFactory $vendorFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        DateTime $dateTimeFilter
    ) {
        $this->vendorFactory = $vendorFactory;
        $this->dateTimeFilter = $dateTimeFilter;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $coreRegistry, $resultForwardFactory, $resultPageFactory, $authSession, $logger);
    }

    public function execute()
    {
        $vendorId = $this->getRequest()->getParam('id');

        $data = $this->getRequest()->getPostValue();

        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data && !empty($vendorId)) {
            try{
                $vendor = $this->loadVendor($vendorId);

                if (!$vendor->getId() || $vendorId !== $vendor->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Unable to save vendor store data'));
                }

                $this->initFromData($vendor, $data['vendor']);
                $vendor->save();

                if($this->isEnableVendorFlat()==1) {
                    $this->updateFlatTable($vendorId,$data['vendor']);
                }
                $this->messageManager->addSuccessMessage('You saved the vendor store data.');
            }
            catch(\Exception $e) {
                $this->_logger->critical($e);
                $this->messageManager->addErrorMessage($e->getMessage());
                $resultRedirect->setPath(
                    'omnyfy_vendor/*/edit',
                    [ 'id' => $vendorId, '_current' => true]
                );
            }
        }
        else {
            $resultRedirect->setPath('omnyfy_vendor/*/');
            $this->messageManager->addErrorMessage('No data to save');
            return $resultRedirect;
        }

        $resultRedirect->setPath('omnyfy_vendor/*/');
        return $resultRedirect;
    }

    protected function loadVendor($vendorId)
    {
        $vendor = $this->vendorFactory->create();
        if ($vendorId) {
            try {
                $vendor->load($vendorId);
            }
            catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        $this->_coreRegistry->register('current_omnyfy_vendor_store', $vendor);
        return $vendor;
    }

    protected function initFromData($vendor, $data)
    {
        unset($data['custom_attributes']);
        unset($data['extension_attributes']);

        $dateFieldFilters = [];
        $attributes = $vendor->getResource()->loadAllAttributes($vendor)
            ->getSortedAttributes($vendor->getAttributeSetId());
        foreach($attributes as $key => $attribute) {
            if ($attribute->getBackend()->getType() == 'datetime') {
                if (array_key_exists($key, $data) && $data[$key] != '') {
                    $dateFieldFilters[$key] = $this->dateTimeFilter;
                }
            }
            if ('image' == $attribute->getFrontendInput() || 'media_image' == $attribute->getFrontendInput() ) {
                if (array_key_exists($key, $data) && is_array($data[$key])) {
                    if (!empty($data[$key]['delete'])) {
                        $data[$key] = null;
                    } else {
                        if (isset($data[$key][0]['name']) && isset($data[$key][0]['tmp_name'])) {
                            $data[$key] = $data[$key][0]['name'];
                        } else {
                            unset($data[$key]);
                        }
                    }
                }
            }
        }

        $inputFilter = new \Zend_Filter_Input($dateFieldFilters, [], $data);
        $data = $inputFilter->getUnescaped();

        if (isset($data['options'])) {
            $vendorOptions = $data['options'];
            unset($data['options']);
        } else {
            $vendorOptions = [];
        }

        $vendor->addData($data);

        return $vendor;
    }

    public function updateFlatTable($vendorId,$data)
    {
        $tableName = "omnyfy_vendor_vendor_flat_".$this->getStoreId();
        $isTableExist = $this->connection->isTableExists($tableName);
        if($isTableExist) {
            $formatData = [
                "address"=>$data['address'],
                "description"=>$data['description'],
                "logo"=>$data['logo'][0]['name'],
                "banner"=>$data['banner'][0]['name']
            ];
            if(isset($data['project_types']) && $data['project_types']){
                $formatData['project_types'] = implode(",",$data['project_types']);
            }
            $this->connection->update($tableName,$formatData, ['entity_id = ?' => (int)$vendorId]);
        }
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    public function isEnableVendorFlat()
    {
        return $this->scopeConfig->getValue('omnyfy_vendor/vendor/flat_vendor');
    }
}
