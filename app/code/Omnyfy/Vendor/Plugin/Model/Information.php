<?php

namespace Omnyfy\Vendor\Plugin\Model;

use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Omnyfy\Vendor\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;

class Information
{
    /**
     * @var CountryFactory
     */
    private $countryFactory;
    /**
     * @var RegionFactory
     */
    private $regionFactory;

    public function __construct(
        CountryFactory $countryFactory,
        RegionFactory $regionFactory
    )
    {
        $this->countryFactory = $countryFactory;
        $this->regionFactory = $regionFactory;
    }

    public function beforeGetStoreInformationObject(Store $store)
    {
        $info = new DataObject([
            'name' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME),
            'phone' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_PHONE),
            'hours' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_HOURS),
            'street_line1' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_STREET_LINE1),
            'street_line2' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_STREET_LINE2),
            'city' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_CITY),
            'postcode' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_POSTCODE),
            'region_id' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_REGION_CODE),
            'country_id' => $store->getConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_COUNTRY_CODE),
            'vat_number' => $store->getConfig(Data::XML_PATH_STORE_INFO_VAT_NUMBER_OMNYFY),
        ]);

        if ($info->getRegionId()) {
            $info->setRegion($this->regionFactory->create()->load($info->getRegionId())->getName());
        }

        if ($info->getCountryId()) {
            $info->setCountry($this->countryFactory->create()->loadByCode($info->getCountryId())->getName());
        }

        return $info;
    }
}