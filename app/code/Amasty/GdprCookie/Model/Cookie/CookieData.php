<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_GdprCookie
 */


declare(strict_types=1);

namespace Amasty\GdprCookie\Model\Cookie;

use Amasty\GdprCookie\Api\CookieManagementInterface;
use Amasty\GdprCookie\Api\Data\CookieGroupsInterface;
use Amasty\GdprCookie\Model\CookieManager;
use Amasty\GdprCookie\Model\OptionSource\Cookie\Types;

class CookieData
{
    /**
     * @var CookieManagementInterface
     */
    private $cookieManagement;

    /**
     * @var Types
     */
    private $cookieTypes;

    /**
     * @var string[]
     */
    private $allowedCookieGroups;

    /**
     * @var array
     */
    private $cookieGroupDataCache = [];

    public function __construct(
        CookieManagementInterface $cookieManagement,
        CookieManager $cookieManager,
        Types $cookieTypes
    ) {
        $this->cookieManagement = $cookieManagement;
        $this->allowedCookieGroups = explode(',', $cookieManager->getAllowCookies());
        $this->cookieTypes = $cookieTypes;
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getGroupData(int $storeId)
    {
        if (!isset($this->cookieGroupDataCache[$storeId])) {
            $groupData = [];
            $allCookiesAllowed = in_array('0', $this->allowedCookieGroups, true);
            $groups = $this->cookieManagement->getGroups($storeId);
            $cookies = $this->cookieManagement->getCookies($storeId);

            foreach ($groups as $group) {
                $groupData[$group->getId()] = [
                    'groupId' => $group->getId(),
                    'isEssential' => $group->isEssential(),
                    'name' => $group->getName(),
                    'description' => $group->getDescription(),
                    'checked' => $allCookiesAllowed || $this->isCookieGroupChecked($group),
                    'cookies' => []
                ];
            }

            if ($groupData) {
                foreach ($cookies as $cookie) {
                    if (isset($groupData[$cookie->getGroupId()])) {
                        $groupData[$cookie->getGroupId()]['cookies'][] = [
                            'name' => $cookie->getName(),
                            'description' => $cookie->getDescription(),
                            'lifetime' => $cookie->getLifetime(),
                            'provider' => $cookie->getProvider(),
                            'type' => $this->cookieTypes->getCookieTypeNameById((int)$cookie->getType())
                        ];
                    }
                }
            }

            $this->cookieGroupDataCache[$storeId] = array_values($groupData);
        }

        return $this->cookieGroupDataCache[$storeId];
    }

    /**
     * @param CookieGroupsInterface $cookieGroup
     * @return bool
     */
    private function isCookieGroupChecked(CookieGroupsInterface $cookieGroup): bool
    {
        return in_array($cookieGroup->getId(), $this->allowedCookieGroups) || $cookieGroup->isEssential();
    }
}
