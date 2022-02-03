<?php
namespace Omnyfy\Cms\Block\Article\View;

use Magento\Framework\View\Element\Template;

class RelatedVendors extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Omnyfy\Vendor\Model\Resource\Location\Collection
     */
    protected $_itemCollection;
    /**
     * @var \Omnyfy\VendorSearch\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Omnyfy\Vendor\Api\VendorRepositoryInterface
     */
    protected $_vendorRepository;
    /**
     * @var \Omnyfy\Vendor\Helper\Media
     */
    protected $_vendorMedia;

    /**
     * @var \Omnyfy\Vendor\Model\Resource\Vendor\CollectionFactory
     */
    protected $_vendorCollectionFactory;

    /**
     * @var \Omnyfy\Vendor\Api\VendorTypeRepositoryInterface
     */
    protected $_vendorTypeRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Omnyfy\Vendor\Api\VendorAttributeRepositoryInterface
     */
    protected $_vendorMetadataService;

    public function __construct
    (
        Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Omnyfy\VendorSearch\Helper\Data $helperData,
        \Omnyfy\Vendor\Helper\Media $vendorMedia,
        \Omnyfy\Vendor\Api\VendorRepositoryInterface $vendorRepository,
        \Omnyfy\Vendor\Model\Resource\Vendor\CollectionFactory $vendorCollectionFactory,
        \Omnyfy\Vendor\Api\VendorTypeRepositoryInterface $vendorTypeRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Omnyfy\Vendor\Api\VendorAttributeRepositoryInterface $vendorMetadataService,
        array $data = []
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->_helperData = $helperData;
        $this->_vendorMedia = $vendorMedia;
        $this->_vendorRepository = $vendorRepository;
        $this->_vendorCollectionFactory = $vendorCollectionFactory;
        $this->_vendorTypeRepository = $vendorTypeRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_vendorMetadataService = $vendorMetadataService;
        parent::__construct($context, $data);
    }

    /**
     * get realted vendors
     * @return \Omnyfy\Vendor\Model\Resource\Location\Collection
     */
    public function getVendors(){
        $article = $this->getArticle();

        $this->_itemCollection = $article->getRelatedServices()
            ->addAttributeToSelect('required_options');


        $this->_itemCollection->setPageSize(
            (int) $this->_scopeConfig->getValue(
                'mfcms/article_view/related_vendors/number_of_vendors',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );

        $this->_itemCollection->load();

        return $this->_itemCollection;
    }

    public function isSearchByLocation($vendorTypeId){
        try {
            $vendorType = $this->_vendorTypeRepository->getById($vendorTypeId, true);
            return $vendorType->getSearchBy();
        }catch (\Exception $exception){
            return false;
        }
    }

    /**
     * Retrieve true if Display Related Vendors enabled
     * @return boolean
     */
    public function displayVendors()
    {
        return (bool) $this->_scopeConfig->getValue(
            'mfcms/article_view/related_vendors/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * @param $locationId
     * @return string
     */
    public function getLocationUrl($locationId){
        $url = $this->_helperData->getLocationUrl();
        return $this->getUrl($url,['id' => $locationId]);
    }

    /**
     * get Image Vendor
     * @param $vendorId
     * @return bool|string
     */
    public function getImage($vendorId){
        try {
            $vendor = $this->_vendorRepository->getById($vendorId);
            if ($vendor) {
                return $this->_vendorMedia->getVendorLogoUrl($vendor);
            }
        }catch(\Exception $exception){
            $this->_logger->debug($exception->getMessage());
        }
        return "";
    }

    public function getLocationVendorData($vendorId)
    {
        try {
            /** @var \Omnyfy\Vendor\Model\Resource\Vendor\Collection $vendorCollection */
            $vendorCollection = $this->_vendorCollectionFactory->create();
            $vendorCollection->addAttributeToSelect('entity_id',["eq",$vendorId]);

            if ($vendorCollection->count() == 1){
                /** @var \Omnyfy\Vendor\Model\Vendor $vendor */
                $vendor = $vendorCollection->getFirstItem();
                return $this->getVendorData($vendor);
            }

        } catch (\Exception $exception){
            $this->_logger->debug($exception->getMessage());
        }
        return null;
    }

    /**
     * @param \Omnyfy\Vendor\Model\Vendor $vendor
     * @return array
     */
    public function getVendorData($vendor){
        try {
            $vendorTypeId = $vendor->getVendorTypeId();
            $vendorType = $this->_vendorTypeRepository->getById($vendorTypeId, true);

            $data = [];

            if (!$vendorType->getSearchBy()){
                $getVendorAttributeSetId = $vendorType->getVendorAttributeSetId();

                /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
                $searchCriteria = $this->_searchCriteriaBuilder->addFilter('attribute_set_id',$getVendorAttributeSetId);
                $attributes = $this->_vendorMetadataService->getList($searchCriteria->create())->getItems();

                /** @var \Omnyfy\Vendor\Api\Data\VendorAttributeInterface $attribute */
                foreach($attributes as $attribute){

                    if ($attribute->getIsVisibleOnFront()){
                        $data[$attribute->getAttributeId()]['id'] = $attribute->getAttributeId();
                        $data[$attribute->getAttributeId()]['code'] = $attribute->getAttributeCode();
                        $data[$attribute->getAttributeId()]['label'] = $attribute->getDefaultFrontendLabel();
                        $data[$attribute->getAttributeId()]['type'] = $attribute->getFrontendInput();

                        if ($vendor) {
                            $customerAttribute = $vendor->getData($attribute->getAttributeCode());
                            if ($customerAttribute) {
                                if ($attribute->getFrontendInput() == "text")
                                    $data[$attribute->getAttributeId()]['data'] = $vendor->getResource()->getAttribute($attribute)->getFrontEnd()->getValue($vendor);

                                if ($attribute->getFrontendInput() == "multiselect"){
                                    $data[$attribute->getAttributeId()]['data'] =
                                        explode(",",$vendor->getResource()->getAttribute($attribute)->getFrontEnd()->getValue($vendor));
                                }
                            }
                        }
                    }
                }
            }

            return $data;
        } catch (\Exception $exception){
            $this->_logger->debug($exception->getMessage());
            return [];
        }
    }


    /**
     * Retrieve articles instance
     *
     * @return \Omnyfy\Cms\Model\Category
     */
    public function getArticle()
    {
        if (!$this->hasData('article')) {
            $this->setData('article',
                $this->_coreRegistry->registry('current_cms_article')
            );
        }
        return $this->getData('article');
    }
}
