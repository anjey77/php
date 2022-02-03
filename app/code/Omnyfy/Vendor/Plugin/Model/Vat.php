<?php

namespace Omnyfy\Vendor\Plugin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Omnyfy\Vendor\Helper\Data;

class Vat
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeGetMerchantVatNumber($store = null)
    {
        return (string)$this->scopeConfig->getValue(
            Data::XML_PATH_STORE_INFO_VAT_NUMBER_OMNYFY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}