<?php
/**
 * Project: CMS M2.
 * User: abhay
 * Date: 27/3/17
 * Time: 3:30 PM
 */
namespace Omnyfy\Cms\Block\Country\View;

use Magento\Framework\View\Element\Template;

class Industry extends \Magento\Framework\View\Element\Template
{
    protected $coreRegistry;
    protected $countryFactory;
    protected $articleFactory;
    protected $categoryFactory;
    protected $dataHelper;
    protected $_currencyFactory;
	
	/**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
	
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
		\Magento\Customer\Model\Session $customerSession,
		\Omnyfy\Cms\Model\ResourceModel\Article\CollectionFactory $articlemodelFactory,
        \Omnyfy\Cms\Helper\Data $dataHelper,
        \Omnyfy\Cms\Model\CountryFactory $countryFactory,
		\Magento\Framework\Stdlib\DateTime\DateTime $date,
		\Magento\Directory\Model\CurrencyFactory $_currencyFactory,
        \Omnyfy\Cms\Model\CategoryFactory $categoryFactory,
        array $data = [])
    {
        $this->coreRegistry = $coreRegistry;
		$this->customerSession = $customerSession;
        $this->countryFactory = $countryFactory;
		$this->articleFactory = $articlemodelFactory;
        $this->categoryFactory = $categoryFactory;
		$this->_date = $date;
		$this->_currencyFactory = $_currencyFactory;
		$this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }
	
	public function getCountry(){
		return $this->coreRegistry->registry('current_country');
	}
	
    /**
     * Return URL for resized CMS Item image
     * 
     * @param Omnyfy_Cms_Model_Article $item
     * @param integer $width
     * @return string|false
     */
    public function getBannerUrl($banner, $width, $height)
    {
        return $this->dataHelper->imageResize($banner, $width, $height);
    }

    public function getLogoUrl($vendorLogo)
    {
        if (empty($vendorLogo)) {
            return false;
        }
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $vendorLogo;
    }
	
	/**
     * Return login url for guest users with referer url
     *
     * @return string
     */
    public function getLoginUrl() {
        $url = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        $login_url = $this->getUrl('customer/account/login', array('referer' => base64_encode($url)));
        return $login_url;
    }
	
	public function isLoggedIn() {
        return $this->customerSession->isLoggedIn();
    }
	
	public function getChildCategories(){
		return $this->categoryFactory->create()->load($this->getCountry()->getIndustryInfoCategory());
	}
	
	public function getCategory($categoryId){
		return $this->categoryFactory->create()->load($categoryId);
	}
	
	public function currencyConvert($amount, $fromCurrency = null, $toCurrency = null){
		if(!$fromCurrency){
		  $fromCurrency = $this->_storeManager->getStore()->getBaseCurrency();
		}
		if(!$toCurrency){
		  $toCurrency = $this->_storeManager->getStore()->getCurrentCurrency();
		}
		if (is_string($fromCurrency)) {
		  $rateToBase = $this->_currencyFactory->create()->load($fromCurrency)->getAnyRate($this->_storeManager->getStore()->getBaseCurrency()->getCode());
		} elseif ($fromCurrency instanceof \Magento\Directory\Model\Currency) {
		  $rateToBase = $fromCurrency->getAnyRate($this->_storeManager->getStore()->getBaseCurrency()->getCode());
		}
		$rateFromBase = $this->_storeManager->getStore()->getBaseCurrency()->getRate($toCurrency);
		// if(!$rateFromBase) return 'NA';
		if($rateToBase && $rateFromBase){
		  $amount = $amount * $rateToBase * $rateFromBase;
		} else {
			return 'NA';
		  #throw new InputException(__('Please correct the target currency.'));
		}
		return number_format((float)$amount, 2, '.', '');
	}
	
	public function getIncomeLevel(){
		$incomeLevel = $this->getCountry()->getIncomeLevel();
		if($incomeLevel == '1'){
			return 'Low';
		} else if($incomeLevel == '2'){
			return 'Lower middle';
		} else if($incomeLevel == '3'){
			return 'Upper middle';
		} else if($incomeLevel == '4'){
			return 'High';
		}
	}
		
	public function getArticleCollection($categoryId){
		$collection = $this->articleFactory->create()->addFieldToSelect('*')
										->join(
											array('category_mapping' => 'omnyfy_cms_article_category'),
											'main_table.article_id = category_mapping.article_id',
											array('category_id' => 'category_id')
										);
		$collection->addFieldToFilter('category_id',$categoryId);								
		$collection->addFieldToFilter('is_active', '1');
		$collection->addFieldToFilter('publish_time', ['lteq' => $this->_date->gmtDate()]);		
		
		return $collection;
	}
	
	public function getProviderUrl($locationId){
        return $this->getUrl('omnyfy_vendor/index/location/id', array('id' => $locationId));
    }	
	
	public function getContentVisible(){
		$industryId = $this->getCountry()->getIndustryInfoCategory();
		$articleCollection = $this->getArticleCollection($industryId);
		if($articleCollection->getSize()>0){
			return true;
		}
		$childIds = $this->getChildCategories()->getChildrenIds();
		if($childIds){
			foreach($childIds as $child){
				$category = $this->getCategory($child);
				if($category->getIsActive()){
					$childArticleCollection = $this->getArticleCollection($child);
					if($childArticleCollection->getSize()>0){
						return true;
						break;
					}
				}
			}
		}
		return false;
	}
	
	public function getHeadingContentVisible(){
		$industryId = $this->getCountry()->getIndustryInfoCategory();
		$childIds = $this->getChildCategories()->getChildrenIds();
		if($childIds){
			foreach($childIds as $child){
				$category = $this->getCategory($child);
				if($category->getIsActive()){
					$childArticleCollection = $this->getArticleCollection($child);
					if($childArticleCollection->getSize()>0){
						return true;
						break;
					}
				}
			}
		}
		return false;
	}
}