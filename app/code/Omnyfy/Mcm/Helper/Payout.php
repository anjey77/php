<?php

namespace Omnyfy\Mcm\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Omnyfy\Mcm\Model\ShippingCalculationFactory;
use Omnyfy\Mcm\Model\ResourceModel\VendorOrder\CollectionFactory as VendorOrderCollectionFactory;

class Payout extends AbstractHelper {

    protected $_storeManager;

    protected $orderTaxManagement;

    protected $priceCurrency;

    protected $feesManagementResource;

    protected $_shippingCalculationFactory;

    protected $vendorOrderCollectionFactory;

    protected $mcmHelper;

    protected $resourceConnection;

    protected $orderRepository;

    protected $_items = [];

    public function __construct(
        Context $context,
        \Magento\Tax\Api\OrderTaxManagementInterface $orderTaxManagement,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrencyInterface,
        \Omnyfy\Mcm\Model\ResourceModel\FeesManagement $feesManagementResource,
        \Omnyfy\Mcm\Model\ShippingCalculationFactory  $shippingCalculationFactory,
        VendorOrderCollectionFactory $vendorOrderCollectionFactory,
        \Omnyfy\Mcm\Helper\Data $mcmHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->_storeManager = $storeManager;
        $this->orderTaxManagement = $orderTaxManagement;
        $this->priceCurrency = $priceCurrencyInterface;
        $this->feesManagementResource = $feesManagementResource;
        $this->_shippingCalculationFactory = $shippingCalculationFactory;
        $this->vendorOrderCollectionFactory = $vendorOrderCollectionFactory;
        $this->mcmHelper = $mcmHelper;
        $this->resourceConnection = $resourceConnection;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    // @TODO - placeholder to vendor payout value
    public function getVendorPayoutValue($vendorId)
    {

    }

    // @TODO - placeholder to check
    public function doesShippingCalculationExist($vendorOrder)
    {
        // Get order shipments that are ship by type 2 which is vendor
        // ship_by_type = 1 (Marketplace Owner)
        // ship_by_type = 2 (Vendor)
        $vendorShipments = $this->_shippingCalculationFactory
            ->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $vendorOrder->getOrderId())
            ->addFieldToFilter('vendor_id', $vendorOrder->getVendorId())
            ->addFieldToFilter('ship_by_type', '2');

        if ($vendorShipments->getSize() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getOrderPayoutShippingAmount($vendorOrder)
    {
        // Get order shipments that are ship by type 2 which is vendor
        // ship_by_type = 1 (Marketplace Owner)
        // ship_by_type = 2 (Vendor)
        $vendorShipments = $this->_shippingCalculationFactory
            ->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $vendorOrder->getOrderId())
            ->addFieldToFilter('vendor_id', $vendorOrder->getVendorId())
            ->addFieldToFilter('ship_by_type', '2');

        $orderShipmentTotal = 0;
        if ($vendorShipments->getSize() > 0) {
            foreach($vendorShipments as $vendorShipment) {
                $orderShipmentTotal += $vendorShipment->getCustomerPaid();
            }
        }

        return $orderShipmentTotal;
    }

    public function getReadyToPayoutVendorOrderCollection($vendorId)
    {
        $vendorOrderCollection = $this->vendorOrderCollectionFactory->create();
        $vendorOrderCollection = $vendorOrderCollection->addFieldToFilter('vendor_id', $vendorId)
            ->addFieldToFilter('payout_status', 0)
            ->addFieldToFilter('payout_action', 1);

        return $vendorOrderCollection;
    }

    public function getPayoutAmount($vendorOrderTotalIncTax, $vendorOrderFeeTotalIncTax, $vendorOrderMcm)
    {
        // This needs to take into account the shipping fees
        $payoutAmount = $vendorOrderTotalIncTax - $vendorOrderFeeTotalIncTax - $vendorOrderMcm->getBaseRefundAmount();

        // If MCM is set not to manage shipping fees, if ship by type is also disabled, pay out to vendor
        if (!$this->mcmHelper->getShipByTypeConfiguration()) {
            if (!$this->mcmHelper->manageShippingFees()) {
                $orderShippingAmount = $this->getOrderShippingFee($vendorOrderMcm);

                if (isset($orderShippingAmount['amount'])) {
                    $payoutAmount += $orderShippingAmount['amount'];
                }
            } elseif ($this->mcmHelper->manageShippingFees()) {
                $orderShippingAmount = $this->getOrderShippingFee($vendorOrderMcm);

                // If there is a record in omnyfy_vendor_quote_shipping, use that value (this table doesn't get populated for methods like flat rate)
                if (isset($orderShippingAmount['amount'])) {
                    $payoutAmount -= $orderShippingAmount['amount'];
                }
                // Otherwise use the base shipping amount that is stored in mcm_order table
                else {
                    $payoutAmount -= $vendorOrderMcm->getBaseShippingAmount() + $vendorOrderMcm->getBaseShippingTax();
                }
            }
        }
        return $payoutAmount;
    }

    public function getOrderShippingFee($order)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('omnyfy_vendor_quote_shipping'); //gives table name with prefix

        $vendorOrder = $this->orderRepository->get($order->getOrderId());
        $quoteId = $vendorOrder->getQuoteId();

        //Select Data from table
        $sql = 'SELECT amount FROM ' . $tableName . ' WHERE `quote_id` =  ' . $quoteId . ' AND vendor_id = ' . $order->getVendorId();
        $result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.

        return $result;

    }
}
