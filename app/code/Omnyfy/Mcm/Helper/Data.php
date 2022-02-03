<?php

namespace Omnyfy\Mcm\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class Data
 * @package Omnyfy\Mcm\Helper
 */
class Data extends AbstractHelper {

    protected $_storeManager;
    protected $orderTaxManagement;

    const XML_PATH = 'omnyfy_mcm/';
    const MCM_ENABLE = 'general/fees_management';
    const TAX_RATE = 'general/tax_rate';
    const REFUND_SHIPPING_PARTIAL = 'general/refund_shipping_partial';
    const REFUND_SHIPPING_FULL = 'general/refund_shipping_full';
    const CATEGORY_COMMISSIONS = 'category_commissions/enable';
    const SELLER_FEES_ENABLE = 'set_default_fees/allow_vendor_fees';
    const DEFAULT_SELLER_FEES = 'set_default_fees/default_seller_fees';
    const DEFAULT_MIN_SELLER_FEES = 'set_default_fees/default_min_seller_fees';
    const DEFAULT_MAX_SELLER_FEES = 'set_default_fees/default_max_seller_fees';
    const DEFAULT_DISBURSMENT_FEES = 'set_default_fees/default_disbursment_fees';
    const PAYOUT_NOTIFICATION_MO = 'email/payout_notification_to_mo_template';
    const PAYOUT_NOTIFICATION_VENDOR = 'email/payout_notification_to_vendor_template';
    const WITHDRAWAL_NOTIFICATION_VENDOR = 'email/withdrawal_notification_to_vendor';

    protected $priceCurrency;

    protected $feesManagementResource;

    protected $_items = [];

    public function __construct(
        Context $context,
        \Magento\Tax\Api\OrderTaxManagementInterface $orderTaxManagement,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrencyInterface,
        \Omnyfy\Mcm\Model\ResourceModel\FeesManagement $feesManagementResource
    ) {
        $this->_storeManager = $storeManager;
        $this->orderTaxManagement = $orderTaxManagement;
        $this->priceCurrency = $priceCurrencyInterface;
        $this->feesManagementResource = $feesManagementResource;
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null) {
        return $this->scopeConfig->getValue(
                        $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null) {
        return $this->getConfigValue(self::XML_PATH . $code, $storeId);
    }

    /**
     * @param $config
     * @return mixed
     */
    public function getConfig($config) {
        return $this->scopeConfig->getValue($config, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get module status
     *
     * @return bool
     */
    public function isEnable() {
        return (bool) $this->getConfig('omnyfy_mcm/general/fees_management');
    }

    /**
     * Get shipping fee management configuration
     *
     * @return bool
     */
    public function manageShippingFees()
    {
        return (bool) $this->getConfig('omnyfy_mcm/general/shipping_fees_management');
    }

    /**
     * Get shipping fee management configuration
     *
     * @return bool
     */
    public function getShipByTypeConfiguration()
    {
        return (bool) $this->getConfig('omnyfy_mcm/general/ship_by_type');
    }

    /**
     * Get Tax Rate on Fees
     *
     * @return float
     */
    public function getTaxRate() {
        return (float) $this->getConfig('omnyfy_mcm/transaction_fees/transaction_fee_tax_rate');
    }

    /**
     * Get Tax Rate on Fees
     *
     * @return bool
     */
    public function allowCategoryCommisssion() {
        return (bool) $this->getConfig('omnyfy_mcm/category_commissions/enable');
    }

    /**
     * Get Transaction Fee status
     *
     * @return bool
     */
    public function isTransactionFeeEnable() {
        return (bool) $this->getConfig('omnyfy_mcm/transaction_fees/allow_transaction_fees');
    }

    /**
     * Get Transaction Fee Percentage
     *
     * @return int
     */
    public function getTransactionFeePercentage() {
        return (float) $this->getConfig('omnyfy_mcm/transaction_fees/transaction_fee_percentage');
    }

    /**
     * Get Transaction Fee Amount
     *
     * @return float
     */
    public function getTransactionFeeAmount() {
        return (float) $this->getConfig('omnyfy_mcm/transaction_fees/transaction_fee_amount');
    }

    /**
     * Get Transaction Fee Surcharge Percentage
     *
     * @return int
     */
    public function getTransactionFeeSurchargePercentage() {
        return (float) $this->getConfig('omnyfy_mcm/transaction_fees/transaction_fee_surcharge_percentage');
    }

    /**
     * Get Vendor Fee status
     *
     * @return bool
     */
    public function isVendorFeeEnable() {
        return (bool) $this->getConfig('omnyfy_mcm/set_default_fees/allow_vendor_fees');
    }

    /**
     * Get Default Seller Fees
     *
     * @return float
     */
    public function getDefaultSellerFees() {
        return (float) $this->getConfig('omnyfy_mcm/set_default_fees/default_seller_fees');
    }

    /**
     * Get Default Seller Fees
     *
     * @return float
     */
    public function getDefaultMinSellerFees() {
        return (float) $this->getConfig('omnyfy_mcm/set_default_fees/default_min_seller_fees');
    }

    /**
     * Get Default Seller Fees
     *
     * @return float
     */
    public function getDefaultMaxSellerFees() {
        return (float) $this->getConfig('omnyfy_mcm/set_default_fees/default_max_seller_fees');
    }

    /**
     * Get Default Seller Fees
     *
     * @return float
     */
    public function getDefaultDisbursementFees() {
        return (float) $this->getConfig('omnyfy_mcm/set_default_fees/default_disbursment_fees');
    }

    public function getShippingTaxPercent($orderId) {
        $orderTaxDetails = $this->orderTaxManagement->getOrderTaxDetails($orderId);
        $itemTaxDetails = $orderTaxDetails->getItems();
        $result = [];
        foreach ($itemTaxDetails as $itemTaxDetail) {
            //Aggregate taxable items associated with shipping
            if ($itemTaxDetail->getType() == \Magento\Quote\Model\Quote\Address::TYPE_SHIPPING) {
                $itemAppliedTaxes = $itemTaxDetail->getAppliedTaxes();
                foreach ($itemAppliedTaxes as $itemAppliedTax) {
                    if (0 == $itemAppliedTax->getAmount() && 0 == $itemAppliedTax->getBaseAmount()) {
                        continue;
                    }
                    $result[$itemAppliedTax->getCode()] = $itemAppliedTax->getPercent();
                }
            }
        }
        return $result;
    }

    public function convertBasePrice($amount = 0, $store = null) {
        if ($amount == 0) {
            return $this->priceCurrency->convert($amount, $store);
        }
        $rate = $this->priceCurrency->convert($amount, $store) / $amount;
        $amount = $amount / $rate;
        return $amount;
    }

    public function formatToBaseCurrency($amount = 0) {
        $baseCurrency = $this->_storeManager->getStore()->getBaseCurrency()->getCode();
        return $this->priceCurrency->format($amount, false, null, null, $baseCurrency);
    }

    public function getVendorOrderItem($itemId) {
        if (!array_key_exists($itemId, $this->_items) || empty($this->_items[$itemId])) {
            $this->_items[$itemId] = $this->feesManagementResource->getVendorOrderItem($itemId);
        }
        return empty($this->_items[$itemId]) ? false : $this->_items[$itemId];
    }

    public function getSellerFeeByItemId($itemId) {
        $item = $this->getVendorOrderItem($itemId);

        if (empty($item) || !isset($item['seller_fee'])) {
            return false;
        }

        return $item['seller_fee'];
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getTemplate($template, $default)
    {
        if ($this->isEnable()) {
            return $template;
        }

        return $default;
    }

    public function getPayoutNotificationTemplate(){
        return $this->getConfigValue(self::PAYOUT_NOTIFICATION_MO, $storeId = null);
    }

    public function getPayoutNotificationToVendorTemplate(){
        return $this->getConfigValue(self::PAYOUT_NOTIFICATION_VENDOR, $storeId = null);
    }

    public function getWithDrawlNotificationToVendorTemplate(){
        return $this->getConfigValue(self::WITHDRAWAL_NOTIFICATION_VENDOR, $storeId = null);
    }

    public function getTemplateNewInvoice()
    {
        if ($this->isEnable()) {
            return 'Omnyfy_Mcm::order/invoice/create/items/renderer/default.phtml';
        }
        return 'Omnyfy_Vendor::order/invoice/create/items/renderer/default.phtml';
    }

    public function getTemplateViewInvoice()
    {
        if ($this->isEnable()) {
            return 'Omnyfy_Mcm::order/invoice/view/items/renderer/default.phtml';
        }
        return 'Omnyfy_Vendor::order/invoice/view/items/renderer/default.phtml';
    }

    public function getTemplateItemsInvoice()
    {
        if ($this->isEnable()) {
            return 'Omnyfy_Mcm::order/invoice/view/items.phtml';
        }
        return 'Omnyfy_Vendor::order/invoice/view/items.phtml';
    }
}
