<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Controller;

use Magento\Framework\App\Http\Context;

class Router implements \Magento\Framework\App\RouterInterface
{
    const DOESNT_EXISTS = 0;
    const EXISTS_ON_ANOTHER_STORE_VIEW = 1;
    const EXISTS = 2;

    /**
     * @var \Magento\Framework\App\ActionFactory
     */
    private $actionFactory;

    /**
     * @var \Amasty\Faq\Model\ResourceModel\Category
     */
    private $category;

    /**
     * @var \Amasty\Faq\Model\ResourceModel\Question
     */
    private $question;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\RequestInterface|\Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Amasty\Faq\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    private $response;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var Context
     */
    private $httpContext;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $url,
        \Amasty\Faq\Model\ResourceModel\Category $category,
        \Amasty\Faq\Model\ResourceModel\Question $question,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Amasty\Faq\Model\ConfigProvider $configProvider,
        Context $httpContext,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->actionFactory = $actionFactory;
        $this->category = $category;
        $this->question = $question;
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->url = $url;
        $this->response = $response;
        $this->messageManager = $messageManager;
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
    }

    /**
     * Match application action by request
     *
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;

        $route = $this->configProvider->getUrlKey();
        if ($this->request->getFrontName() !== $route
            || $this->request->getModuleName()
            || !$this->configProvider->isEnabled()
        ) {
            return null;
        }

        $this->request->setModuleName('faq');
        $urlKey = $this->getUrlKeyFromRequest();
        $matchCategory = $this->matchCategory($urlKey);
        $matchQuestion = $this->matchQuestion($urlKey);
        if ($urlKey) {
            if (!$matchCategory && !$matchQuestion && !$this->matchStat($urlKey)) {
                return null;
            } elseif (($matchQuestion == self::EXISTS_ON_ANOTHER_STORE_VIEW)
                || ($matchCategory == self::EXISTS_ON_ANOTHER_STORE_VIEW)
            ) {
                $this->messageManager->addErrorMessage(
                    __('This question or category is not available for the current store view')
                );
                $this->response->setRedirect($this->url->getUrl($route));

                return $this->actionFactory->create(\Magento\Framework\App\Action\Redirect::class);
            }
        } else {
            $this->request->setControllerName('index')->setActionName('index');
        }

        $path = ltrim($this->request->getPathInfo(), '/');
        if (rtrim($path, '/') . '/' !== $path && !$this->configProvider->isUseFaqCmsHomePage()) {
            $this->response->setRedirect($this->url->getUrl(rtrim($path, '/') . '/'), 301);
            $request->setDispatched(true);
            return $this->actionFactory->create(\Magento\Framework\App\Action\Redirect::class);
        }

        return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
    }

    private function getUrlKeyFromRequest(): string
    {
        $configUrlKey = preg_quote($this->configProvider->getUrlKey());
        $urlKeyPart = preg_replace(
            "/{$configUrlKey}/",
            '',
            $this->request->getPathInfo(),
            1
        );

        return urldecode(trim($urlKeyPart, '/'));
    }

    /**
     * @param string $urlKey
     *
     * @return bool
     */
    private function matchCategory($urlKey)
    {
        $categoryExists = self::DOESNT_EXISTS;
        $currentStore = $this->storeManager->getStore()->getId();
        $categoryIds = $this->category->getStoresForUrl($urlKey);

        if (!$categoryIds) {
            return $categoryExists;
        }

        foreach ($categoryIds as $categoryId) {
            if ($categoryId['store_id'] == $currentStore
                || $categoryId['store_id'] == \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ) {
                $this->request->setControllerName('category')
                    ->setActionName('view')
                    ->setParam('id', $categoryId['category_id']);
                $categoryExists = self::EXISTS;
                break;
            } else {
                $categoryExists = self::EXISTS_ON_ANOTHER_STORE_VIEW;
            }
        }

        return $categoryExists;
    }

    /**
     * @param string $urlKey
     *
     * @return bool
     */
    private function matchQuestion($urlKey)
    {
        $questionExists = self::DOESNT_EXISTS;
        $currentStore = (int)$this->storeManager->getStore()->getId();
        $isLoggedIn = $this->customerSession->isLoggedIn();
        $questionIds = $this->question->getVisibleQuestionsByUrlKey($urlKey, $isLoggedIn);

        if (!$questionIds) {
            return $questionExists;
        }

        foreach ($questionIds as $questionId) {
            if ($questionId['store_id'] == $currentStore
                || $questionId['store_id'] == \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ) {
                $this->request->setControllerName('question')
                    ->setActionName('view')
                    ->setParam('id', $questionId['question_id']);
                $questionExists = self::EXISTS;
                break;
            } else {
                $questionExists = self::EXISTS_ON_ANOTHER_STORE_VIEW;
            }
        }

        return $questionExists;
    }

    private function matchStat($urlKey)
    {
        if ($urlKey == 'stat' && $this->request->isPost()) {
            $this->request->setControllerName('stat')
                ->setActionName('collect');

            return true;
        }

        return false;
    }
}
