<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Omnyfy\Vendor\Plugin\Magento\Framework;

use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Phrase;

/**
 * Timezone library
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Timezone Extends \Magento\Framework\Stdlib\DateTime\Timezone
{
    /**
     * @var array
     */
    protected $_allowedFormats = [
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    ];

    /**
     * @var string
     */
    protected $_scopeType;

    /**
     * @var \Magento\Framework\App\ScopeResolverInterface
     */
    protected $_scopeResolver;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     * @var string
     */
    protected $_defaultTimezonePath;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @param \Magento\Framework\App\ScopeResolverInterface $scopeResolver
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param string $scopeType
     * @param string $defaultTimezonePath
     */
    public function __construct(
        \Magento\Framework\App\ScopeResolverInterface $scopeResolver,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $scopeType,
        $defaultTimezonePath
    ) {
        parent::__construct($scopeResolver, $localeResolver, $dateTime, $scopeConfig, $scopeType, $defaultTimezonePath);
        $this->_scopeResolver = $scopeResolver;
        $this->_localeResolver = $localeResolver;
        $this->_dateTime = $dateTime;
        $this->_defaultTimezonePath = $defaultTimezonePath;
        $this->_scopeConfig = $scopeConfig;
        $this->_scopeType = $scopeType;
    }

    public function scopeDate($scope = null, $date = null, $includeTime = false)
    {
        $timezone = new \DateTimeZone(
            $this->_scopeConfig->getValue($this->getDefaultTimezonePath(), $this->_scopeType, $scope)
        );
        switch (true) {
            case (empty($date)):
                $date = new \DateTime('now', $timezone);
                break;
            case ($date instanceof \DateTime):
            case ($date instanceof \DateTimeImmutable):
                $date = $date->setTimezone($timezone);
                break;
            default:
                $date = new \DateTime(is_numeric($date) ? '@' . $date : $date);
                $date->setTimezone($timezone);
                break;
        }

        if (!$includeTime) {
            $date->setTime(0, 0, 0);
        }

        return $date;
    }

}
