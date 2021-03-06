<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_GdprCookie
 */


namespace Amasty\GdprCookie\Model\Repository;

use Amasty\GdprCookie\Api\CookieRepositoryInterface;
use Amasty\GdprCookie\Api\Data\CookieInterface;
use Amasty\GdprCookie\Model\CookieFactory;
use Amasty\GdprCookie\Model\ResourceModel\Cookie as CookieResource;
use Amasty\GdprCookie\Model\ResourceModel\Cookie\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class CookieRepository implements CookieRepositoryInterface
{
    /**
     * @var CookieFactory
     */
    private $cookieFactory;

    /**
     * @var CookieResource
     */
    private $cookieResource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Model data storage
     *
     * @var array
     */
    private $cookies;

    public function __construct(
        CookieFactory $cookieFactory,
        CookieResource $cookieResource,
        CollectionFactory $collectionFactory
    ) {
        $this->cookieFactory = $cookieFactory;
        $this->cookieResource = $cookieResource;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(CookieInterface $cookie, int $storeId = 0)
    {
        try {
            if ($cookie->getId()) {
                $cookie = $this->getById($cookie->getId())
                    ->addData($cookie->getData());
            }

            $this->cookieResource->setStoreId($storeId);
            $this->cookieResource->save($cookie);
            unset($this->cookies[$cookie->getId()]);
        } catch (\Exception $e) {
            if ($cookie->getId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save cookie with ID %1. Error: %2',
                        [$cookie->getId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new cookie. Error: %1', $e->getMessage()));
        }

        return $cookie;
    }

    /**
     * @inheritdoc
     */
    public function getById($cookieId, int $storeId = 0)
    {
        if (!isset($this->cookie[$cookieId][$storeId])) {
            /** @var \Amasty\GdprCookie\Model\Cookie $cookie */
            $cookie = $this->cookieFactory->create();
            $this->cookieResource->setStoreId($storeId);
            $this->cookieResource->load($cookie, $cookieId);

            if (!$cookie->getId()) {
                throw new NoSuchEntityException(__('Cookie with specified ID "%1" not found.', $cookieId));
            }
            $this->cookies[$cookieId][$storeId] = $cookie;
        }

        return $this->cookies[$cookieId][$storeId];
    }

    /**
     * @inheritdoc
     */
    public function getByName($cookieName)
    {
        /** @var \Amasty\GdprCookie\Model\Cookie $cookie */
        $cookie = $this->cookieFactory->create();
        $this->cookieResource->load($cookie, $cookieName, CookieInterface::NAME);

        if (!$cookie->getId()) {
            throw new NoSuchEntityException(__('Cookie with specified Name "%1" not found.', $cookieName));
        }

        return $cookie;
    }

    /**
     * @inheritdoc
     */
    public function delete(CookieInterface $cookie)
    {
        try {
            $this->cookieResource->delete($cookie);
            unset($this->cookies[$cookie->getId()]);
        } catch (\Exception $e) {
            if ($cookie->getId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove cookie with ID %1. Error: %2',
                        [$cookie->getId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove cookie. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($cookieId)
    {
        $cookie = $this->getById($cookieId);

        $this->delete($cookie);
    }
}
