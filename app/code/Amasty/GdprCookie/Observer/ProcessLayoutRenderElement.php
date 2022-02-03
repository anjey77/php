<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_GdprCookie
 */


namespace Amasty\GdprCookie\Observer;

use Amasty\GdprCookie\Model\ConfigProvider;
use Amasty\GdprCookie\Model\CookiePolicy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;

class ProcessLayoutRenderElement implements ObserverInterface
{
    const BLOCK_NAME = 'gdprcookie_bar_footer';
    const BLOCK_PARENT_TOP = 'after.body.start';
    const BLOCK_PARENT_BOTTOM = 'root';

    /**
     * @var bool
     */
    private $processed = false;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CookiePolicy
     */
    private $cookiePolicy;

    public function __construct(
        ConfigProvider $configProvider,
        CookiePolicy $cookiePolicy
    ) {
        $this->configProvider = $configProvider;
        $this->cookiePolicy = $cookiePolicy;
    }

    public function execute(Observer $observer)
    {
        if (!$this->processed && $this->cookiePolicy->isCookiePolicyAllowed()) {
            $event = $observer->getEvent();
            /** @var Layout $layout */
            $layout = $event->getLayout();
            $blockParent = self::BLOCK_PARENT_BOTTOM;

            if ((int)$this->configProvider->getBarLocation()) {
                $blockParent = self::BLOCK_PARENT_TOP;
            }

            if ($layout->hasElement($blockParent)) {
                $layout->addBlock(
                    \Amasty\GdprCookie\Block\CookieBar::class,
                    self::BLOCK_NAME,
                    $blockParent
                );
            }

            $this->processed = true;
        }
    }
}
