<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_GdprCookie
 */


declare(strict_types=1);

namespace Amasty\GdprCookie\Controller\Cookie;

use Amasty\GdprCookie\Model\Cookie;
use Amasty\GdprCookie\Model\Cookie\CookieData;
use Amasty\GdprCookie\Model\CookieGroup;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

class Cookies extends Action
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $pageCacheConfig;

    /**
     * @var CookieData
     */
    private $cookieData;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $pageCacheConfig,
        CookieData $cookieData
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->pageCacheConfig = $pageCacheConfig;
        $this->cookieData = $cookieData;
    }

    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        /** @var ResponseInterface $response */
        $response = $this->getResponse();
        $resultJson->setHeader(
            'X-Magento-Tags',
            implode(
                ',',
                [
                    CookieGroup::CACHE_TAG,
                    Cookie::CACHE_TAG
                ]
            )
        );
        $response->setPublicHeaders($this->pageCacheConfig->getTtl());
        $storeId = (int)$this->storeManager->getStore()->getId();
        $resultJson->setData($this->cookieData->getGroupData($storeId));

        return $resultJson;
    }
}
