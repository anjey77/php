<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_GdprCookie
 */


namespace Amasty\GdprCookie\Block\Widget;

use Amasty\GdprCookie\Model\ConfigProvider;
use Amasty\GdprCookie\Model\Cookie;
use Amasty\GdprCookie\Model\CookieGroup;
use Amasty\GdprCookie\Model\CookiePolicy;
use Amasty\GdprCookie\Model\Cookie\CookieData;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class Settings extends Template implements BlockInterface, IdentityInterface
{
    protected $_template = "widget/settings.phtml";

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CookieData
     */
    private $cookieData;

    /**
     * @var CookiePolicy
     */
    private $cookiePolicy;

    public function __construct(
        ConfigProvider $configProvider,
        CookieData $cookieData,
        Template\Context $context,
        CookiePolicy $cookiePolicy,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->cookieData = $cookieData;
        $this->cookiePolicy = $cookiePolicy;
    }

    /**
     * @return array
     */
    public function getGroupData()
    {
        $storeId = $this->_storeManager->getStore()->getId();

        return $this->cookieData->getGroupData($storeId);
    }

    public function isNeedToShow(): bool
    {
        return $this->cookiePolicy->isCookiePolicyAllowed();
    }

    public function getIdentities()
    {
        return [Cookie::CACHE_TAG, CookieGroup::CACHE_TAG];
    }

    public function getCacheLifetime()
    {
        return null;
    }

    public function getCacheTags()
    {
        return $this->getIdentities();
    }
}
