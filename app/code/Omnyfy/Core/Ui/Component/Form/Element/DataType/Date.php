<?php
/**
 * Project: Service Booking.
 * User: jing
 * Date: 5/12/17
 * Time: 9:08 PM
 */
namespace Omnyfy\Core\Ui\Component\Form\Element\DataType;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class Date
 */
class Date extends \Magento\Ui\Component\Form\Element\DataType\AbstractDataType
{
    const NAME = 'date';

    /**
     * Current locale
     *
     * @var string
     */
    protected $locale;

    /**
     * Wrapped component
     *
     * @var UiComponentInterface
     */
    protected $wrappedComponent;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param TimezoneInterface $localeDate
     * @param ResolverInterface $localeResolver
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        TimezoneInterface $localeDate,
        ResolverInterface $localeResolver,
        array $components = [],
        array $data = []
    ) {
        $this->locale = $localeResolver->getLocale();
        $this->localeDate = $localeDate;
        parent::__construct($context, $components, $data);
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $config = $this->getData('config');

        if (!isset($config['storeTimeZone'])) {
            $storeTimeZone = $this->localeDate->getConfigTimezone();
            $config['storeTimeZone'] = $storeTimeZone;
        }
        // Set date format pattern by current locale
        $localeDateFormat = $this->localeDate->getDateFormat();
        if (!isset($config['options']['dateFormat'])) {
            $config['options']['dateFormat'] = $localeDateFormat;
        }
        if (!isset($config['options']['storeLocale'])) {
            $config['options']['storeLocale'] = $this->locale;
        }
        $this->setData('config', $config);
        parent::prepare();
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        return static::NAME;
    }

    /**
     * Convert given date to default (UTC) timezone
     *
     * @param int $date
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return \DateTime|null
     */
    public function convertDate($date, $hour = 0, $minute = 0, $second = 0)
    {
        try {
            $dateObj = $this->localeDate->date(
                new \DateTime(
                    $date,
                    new \DateTimeZone($this->localeDate->getConfigTimezone())
                ),
                $this->getLocale(),
                true
            );
            $dateObj->setTime($hour, $minute, $second);
            //convert store date to default date in UTC timezone without DST
            $dateObj->setTimezone(new \DateTimeZone('UTC'));
            return $dateObj;
        } catch (\Exception $e) {
            return null;
        }
    }
}