<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_GdprCookie
 */


declare(strict_types=1);

namespace Amasty\GdprCookie\Block;

use Amasty\GdprCookie\Model\ConfigProvider;
use Amasty\GdprCookie\Model\CookiePolicy;
use Magento\Cms\Model\Template\Filter as CmsTemplateFilter;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;

class CookieBar extends Template
{
    const HOMEPAGE_KEY = 'homepage';
    const POLICY_TEXT_COLOR = 'policy_text_color';
    const BACKGROUND_COLOR = 'background_color';
    const ACCEPT_BUTTON_COLOR = 'accept_button_color';
    const ACCEPT_BUTTON_COLOR_HOVER = 'accept_button_color_hover';
    const ACCEPT_TEXT_COLOR = 'accept_text_color';
    const ACCEPT_TEXT_COLOR_HOVER = 'accept_text_color_hover';
    const ACCEPT_BUTTON_TEXT = 'accept_button_text';
    const LINKS_COLOR = 'links_color';
    const COOKIES_BAR_LOCATION = 'cookies_bar_location';
    const SETTINGS_BUTTON_COLOR = 'settings_button_color';
    const SETTINGS_BUTTON_COLOR_HOVER = 'settings_button_color_hover';
    const SETTINGS_TEXT_COLOR = 'settings_text_color';
    const SETTINGS_TEXT_COLOR_HOVER = 'settings_text_color_hover';
    const SETTINGS_BUTTON_TEXT = 'settings_button_text';
    const GROUP_TITLE_TEXT_COLOR = 'group_title_text_color';
    const GROUP_DESCRIPTION_TEXT_COLOR = 'group_description_text_color';

    /**
     * @var string
     */
    protected $_template = 'Amasty_GdprCookie::cookiebar.phtml';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var CmsTemplateFilter
     */
    private $cmsTemplateFilter;

    /**
     * @var CookiePolicy
     */
    private $cookiePolicy;

    public function __construct(
        ConfigProvider $configProvider,
        Template\Context $context,
        Json $jsonSerializer,
        CmsTemplateFilter $cmsTemplateFilter,
        CookiePolicy $cookiePolicy,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        // backward compatibility with the Magento 2.1, to avoid compilation issue;
        $this->urlInterface = $context->getUrlBuilder() ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(UrlInterface::class);
        $this->jsonSerializer = $jsonSerializer;
        $this->cmsTemplateFilter = $cmsTemplateFilter;
        $this->cookiePolicy = $cookiePolicy;
    }

    /**
     * @return int
     */
    public function isProcessFirstShow()
    {
        return $this->configProvider->getFirstVisitShow();
    }

    /**
     * @return string
     */
    public function getNotificationText()
    {
        $text = $this->cmsTemplateFilter->filter($this->configProvider->getNotificationText());

        return $this->jsonSerializer->serialize($text);
    }

    /**
     * @return string
     */
    public function getAllowLink()
    {
        return $this->_urlBuilder->getUrl('gdprcookie/cookie/allow');
    }

    /**
     * @return int
     */
    public function getNoticeType()
    {
        return (int)$this->configProvider->getCookiePrivacyBarType();
    }

    /**
     * @return bool
     */
    public function isNotice()
    {
        return $this->cookiePolicy->isCookiePolicyAllowed();
    }

    /**
     * @return int
     */
    public function getWebsiteInteraction()
    {
        $websiteInteraction = (int)$this->configProvider->getCookieWebsiteInteraction();

        if ($websiteInteraction && $this->isAllowedPage($this->urlInterface->getCurrentUrl())) {
            return 0;
        }

        return $websiteInteraction;
    }

    /**
     * @return null|string
     */
    public function getPolicyTextColor()
    {
        if (!$this->hasData(self::POLICY_TEXT_COLOR)) {
            $this->setData(self::POLICY_TEXT_COLOR, $this->configProvider->getPolicyTextColor());
        }

        return $this->getData(self::POLICY_TEXT_COLOR);
    }

    /**
     * @return null|string
     */
    public function getBackgroundColor()
    {
        if (!$this->hasData(self::BACKGROUND_COLOR)) {
            $this->setData(self::BACKGROUND_COLOR, $this->configProvider->getBackgroundColor());
        }

        return $this->getData(self::BACKGROUND_COLOR);
    }

    /**
     * @return null|string
     */
    public function getAcceptButtonColor()
    {
        if (!$this->hasData(self::ACCEPT_BUTTON_COLOR)) {
            $this->setData(self::ACCEPT_BUTTON_COLOR, $this->configProvider->getAcceptButtonColor());
        }

        return $this->getData(self::ACCEPT_BUTTON_COLOR);
    }

    /**
     * @return null|string
     */
    public function getAcceptButtonColorHover()
    {
        if (!$this->hasData(self::ACCEPT_BUTTON_COLOR_HOVER)) {
            $this->setData(self::ACCEPT_BUTTON_COLOR_HOVER, $this->configProvider->getAcceptButtonColorHover());
        }

        return $this->getData(self::ACCEPT_BUTTON_COLOR_HOVER);
    }

    /**
     * @return null|string
     */
    public function getAcceptTextColor()
    {
        if (!$this->hasData(self::ACCEPT_TEXT_COLOR)) {
            $this->setData(self::ACCEPT_TEXT_COLOR, $this->configProvider->getAcceptTextColor());
        }

        return $this->getData(self::ACCEPT_TEXT_COLOR);
    }

    /**
     * @return null|string
     */
    public function getAcceptTextColorHover()
    {
        if (!$this->hasData(self::ACCEPT_TEXT_COLOR_HOVER)) {
            $this->setData(self::ACCEPT_TEXT_COLOR_HOVER, $this->configProvider->getAcceptTextColorHover());
        }

        return $this->getData(self::ACCEPT_TEXT_COLOR_HOVER);
    }

    /**
     * @return null|string
     */
    public function getAcceptButtonName()
    {
        if (!$this->hasData(self::ACCEPT_BUTTON_TEXT)) {
            $this->setData(self::ACCEPT_BUTTON_TEXT, $this->configProvider->getAcceptButtonName());
        }

        return $this->getDataByKey(self::ACCEPT_BUTTON_TEXT);
    }

    /**
     * @return null|string
     */
    public function getLinksColor()
    {
        if (!$this->hasData(self::LINKS_COLOR)) {
            $this->setData(self::LINKS_COLOR, $this->configProvider->getLinksColor());
        }

        return $this->getData(self::LINKS_COLOR);
    }

    /**
     * @return null|string
     */
    public function getBarLocation()
    {
        if (!$this->hasData(self::COOKIES_BAR_LOCATION)) {
            $this->setData(self::COOKIES_BAR_LOCATION, $this->configProvider->getBarLocation());
        }

        return $this->getData(self::COOKIES_BAR_LOCATION);
    }

    /**
     * Convert string to array
     *
     * @param string $string
     * @return array|false
     */
    protected function stringValidationAndConvertToArray($string)
    {
        $validate = function ($urls) {
            return preg_split('|\s*[\r\n]+\s*|', $urls, -1, PREG_SPLIT_NO_EMPTY);
        };

        return $validate($string);
    }

    /**
     * Check if current page is allowed for interaction
     *
     * @param string $currentUrl
     *
     * @return bool
     */
    protected function isAllowedPage($currentUrl)
    {
        $urls = trim($this->configProvider->getAllowedUrls());
        $urls = $urls ? $this->stringValidationAndConvertToArray($urls) : [];

        if (in_array(self::HOMEPAGE_KEY, $urls)
            && $this->isHomePage()
        ) {
            return true;
        }

        foreach ($urls as $url) {
            if (false !== strpos($currentUrl, $url)) {
                return true;
            }
        }

        return false;
    }

    private function isHomePage()
    {
        $currentUrl = $this->getUrl('', ['_current' => true]);
        $urlRewrite = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);

        return $currentUrl == $urlRewrite;
    }

    /**
     * @return null|string
     */
    public function getSettingsButtonColor()
    {
        if (!$this->hasData(self::SETTINGS_BUTTON_COLOR)) {
            $this->setData(self::SETTINGS_BUTTON_COLOR, $this->configProvider->getSettingsButtonColor());
        }

        return $this->getDataByKey(self::SETTINGS_BUTTON_COLOR);
    }

    /**
     * @return null|string
     */
    public function getSettingsButtonColorHover()
    {
        if (!$this->hasData(self::SETTINGS_BUTTON_COLOR_HOVER)) {
            $this->setData(self::SETTINGS_BUTTON_COLOR_HOVER, $this->configProvider->getSettingsButtonColorHover());
        }

        return $this->getDataByKey(self::SETTINGS_BUTTON_COLOR_HOVER);
    }

    /**
     * @return null|string
     */
    public function getSettingsTextColor()
    {
        if (!$this->hasData(self::SETTINGS_TEXT_COLOR)) {
            $this->setData(self::SETTINGS_TEXT_COLOR, $this->configProvider->getSettingsTextColor());
        }

        return $this->getDataByKey(self::SETTINGS_TEXT_COLOR);
    }

    /**
     * @return null|string
     */
    public function getSettingsTextColorHover()
    {
        if (!$this->hasData(self::SETTINGS_TEXT_COLOR_HOVER)) {
            $this->setData(self::SETTINGS_TEXT_COLOR_HOVER, $this->configProvider->getSettingsTextColorHover());
        }

        return $this->getDataByKey(self::SETTINGS_TEXT_COLOR_HOVER);
    }

    /**
     * @return null|string
     */
    public function getSettingsButtonName()
    {
        if (!$this->hasData(self::SETTINGS_BUTTON_TEXT)) {
            $this->setData(self::SETTINGS_BUTTON_TEXT, $this->configProvider->getSettingsButtonName());
        }

        return $this->getDataByKey(self::SETTINGS_BUTTON_TEXT);
    }

    /**
     * @return null|string
     */
    public function getTitleTextColor()
    {
        if (!$this->hasData(self::GROUP_TITLE_TEXT_COLOR)) {
            $this->setData(self::GROUP_TITLE_TEXT_COLOR, $this->configProvider->getTitleTextColor());
        }

        return $this->getDataByKey(self::GROUP_TITLE_TEXT_COLOR);
    }

    /**
     * @return null|string
     */
    public function getDescriptionTextColor()
    {
        if (!$this->hasData(self::GROUP_DESCRIPTION_TEXT_COLOR)) {
            $this->setData(self::GROUP_DESCRIPTION_TEXT_COLOR, $this->configProvider->getDescriptionTextColor());
        }

        return $this->getDataByKey(self::GROUP_DESCRIPTION_TEXT_COLOR);
    }
}
