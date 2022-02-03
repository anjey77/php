<?php
namespace Omnyfy\ManualPayout\Controller\Adminhtml\PendingPayouts;

use Magento\Ui\Component\MassAction\Filter;
use Omnyfy\Mcm\Model\ResourceModel\PayoutType\CollectionFactory as PayoutTypeCollection;
use Omnyfy\Mcm\Model\ResourceModel\VendorOrder\CollectionFactory as VendorOrderCollectionFactory;
use Omnyfy\Mcm\Model\ResourceModel\VendorPayout\CollectionFactory as VendorPayoutCollection;
use Omnyfy\Mcm\Model\ResourceModel\VendorPayout\CollectionFactory as VendorPayoutCollectionFactory;
use Omnyfy\Mcm\Model\ResourceModel\VendorPayoutType\CollectionFactory as VendorPayoutTypeCollection;
use Omnyfy\Mcm\Model\SequenceFactory;
use Omnyfy\Mcm\Model\VendorPayoutHistoryFactory;
use Omnyfy\Mcm\Model\VendorPayoutInvoice\VendorPayoutInvoiceOrderFactory;
use Omnyfy\Mcm\Model\VendorPayoutInvoiceFactory;

class MassProcessManualPayouts extends \Omnyfy\Mcm\Controller\Adminhtml\PendingPayouts\MassProcessPayouts
{
    const PAYOUT_TYPE = "Manual";

    protected $payoutTypeCollection;

    protected $vendorPayoutTypeCollection;

    protected $vendorPayoutCollection;

    protected $emptyMessage = "No Payouts Processed.";

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Psr\Log\LoggerInterface $logger,
        Filter $filter,
        VendorPayoutCollectionFactory $vendorPayoutCollectionFactory,
        VendorOrderCollectionFactory $vendorOrderCollectionFactory,
        VendorPayoutHistoryFactory $vendorPayoutHistoryFactory,
        SequenceFactory $sequenceFactory,
        VendorPayoutInvoiceFactory $vendorPayoutInvoiceFactory,
        VendorPayoutInvoiceOrderFactory $vendorPayoutInvoiceOrderFactory,
        \Omnyfy\Mcm\Model\Config $config,
        \Omnyfy\Mcm\Helper\Data $mcmHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Omnyfy\Mcm\Helper\Payout $payoutHelper,
        PayoutTypeCollection $payoutTypeCollection,
        VendorPayoutTypeCollection $vendorPayoutTypeCollection,
        VendorPayoutCollection $vendorPayoutCollection
    ) {
        $this->payoutTypeCollection = $payoutTypeCollection;
        $this->vendorPayoutTypeCollection = $vendorPayoutTypeCollection;
        $this->vendorPayoutCollection = $vendorPayoutCollection;
        parent::__construct($context, $coreRegistry, $resultForwardFactory, $resultPageFactory, $authSession, $logger, $filter, $vendorPayoutCollectionFactory, $vendorOrderCollectionFactory, $vendorPayoutHistoryFactory, $sequenceFactory, $vendorPayoutInvoiceFactory, $vendorPayoutInvoiceOrderFactory, $config, $mcmHelper, $resourceConnection, $orderRepository, $payoutHelper);
    }

    protected function getPayoutTypeId($payoutType = null)
    {
        $payoutType = $payoutType === null ? self::PAYOUT_TYPE : $payoutType;
        $typeId = $this->payoutTypeCollection->create()->addFieldToFilter('payout_type', ['eq' => $payoutType])->getFirstItem()->getId();
        return $typeId;
    }

    protected function getVendorPayoutType($vendorId)
    {
        $vendorPayoutType = $this->vendorPayoutTypeCollection->create()
            ->addFieldToFilter('vendor_id', ['eq' => $vendorId])->getFirstItem();
        return $vendorPayoutType->getPayoutTypeId();
    }

    protected function isProcessPayoutForStripe($vendorId)
    {
        $stripePayoutTypeId = $this->getPayoutTypeId("Stripe");
        $currentVendorPayoutTypeId = $this->getVendorPayoutType($vendorId);

        if (!empty($stripePayoutTypeId) && ($currentVendorPayoutTypeId == $stripePayoutTypeId)) {
            return true;
        }
        return false;
    }

    public function vendorManualPayoutsProcess($payout, $payoutRef) {
        $vendorTotalPayoutAmount = 0.00;
        $vendorPayoutInvoiceData = [
            'payout_ref' => $payoutRef,
            'increment_id' => $this->getRef('invoice_ref'),
            'vendor_id' => $payout->getVendorId(),
            'orders_total_incl_tax' => 0.00,
            'orders_total_tax' => 0.00,
            'fees_total_incl_tax' => 0.00,
            'fees_total_tax' => 0.00,
            'shipping_total_for_order' => 0.00
        ];
        $vendorId = $payout->getVendorId();
        $vendorName = $payout->getVendorName();
        $payoutId = $payout->getPayoutId();
        $accountRef = $payout->getAccountRef();
        $vendorOrderCollection = $this->getVendorOrderCollection($vendorId);
        $vendorOrderCollection->getSelect()
            ->join(
                ['payment' => $vendorOrderCollection->getTable('sales_order_payment')],
                'payment.parent_id = main_table.order_id',
                ['method']
            )->where('method not like ?', 'stripe_payments');
        $updatedOrderCount = 0;

        // vendor collection will always not be empty, ensure that there is records
        if (!empty($vendorOrderCollection) && count($vendorOrderCollection) > 0) {
            $vendorOrderIds = [];

            foreach ($vendorOrderCollection as $vendorOrder) {
                $updatedOrderCount++;
                $vendorOrder->setPayoutStatus(3); //Payout status 3 => In progress
                $vendorOrder->save();
                //manage payout history

                // If there is a record in omnyfy_mcm_shipping_calculation use that for order
                $doesShippingMcmCalculationExist = $this->payoutHelper->doesShippingCalculationExist($vendorOrder);
                if ($doesShippingMcmCalculationExist) {
                    $orderTotalIncludingShippingPayout = $this->payoutHelper->getOrderPayoutShippingAmount($vendorOrder);
                    $vendorOrderTotalIncTax = ($vendorOrder->getBaseGrandTotal() + ($orderTotalIncludingShippingPayout - $vendorOrder->getShippingDiscountAmount()));
                    $vendorPayoutInvoiceData['shipping_total_for_order'] = $orderTotalIncludingShippingPayout - $vendorOrder->getShippingDiscountAmount();
                } else {
                    // fallback for existing
                    $vendorOrderTotalIncTax = ($vendorOrder->getBaseGrandTotal() + ($vendorOrder->getBaseShippingAmount() + $vendorOrder->getBaseShippingTax() - $vendorOrder->getShippingDiscountAmount()));
                }

                $vendorOrderTotalFees = $vendorOrder->getTotalCategoryFee() + $vendorOrder->getTotalSellerFee() + $vendorOrder->getDisbursementFee();
                $vendorOrderTotalFeeTax = $vendorOrder->getTotalCategoryFeeTax() + $vendorOrder->getTotalSellerFeeTax() + $vendorOrder->getDisbursementFeeTax();
                $vendorOrderFeeTotalIncTax = $vendorOrderTotalFees + $vendorOrderTotalFeeTax;

                // This needs to take into account the shipping fees
                $payoutAmount = $vendorOrderTotalIncTax - $vendorOrderFeeTotalIncTax;

                // If MCM is set not to manage shipping fees, if ship by type is also disabled, pay out to vendor
                if (!$this->mcmHelper->getShipByTypeConfiguration()) {
                    if (!$this->mcmHelper->manageShippingFees()) {
                        $orderShippingAmount = $this->getOrderShippingFee($vendorOrder);

                        if (isset($orderShippingAmount['amount'])) {
                            $payoutAmount += $orderShippingAmount['amount'];
                        }
                    }
                }
                $payoutAmount = $vendorOrder->getPayoutAmount() > 0 ? $payoutAmount : 0;

                $vendorPayoutHistoryData = [
                    'payout_id' => $payoutId,
                    'vendor_id' => $vendorId,
                    'vendor_order_id' => $vendorOrder->getId(),
                    'payout_ref' => $payoutRef,
                    'payout_amount' => $payoutAmount, //$vendorOrder->getPayoutAmount(),
                    'status' => 2 // 3 = In progress, 4 = Processed - awaiting settlement
                ];

                $this->saveVendorPayoutHistory($vendorPayoutHistoryData);
                $vendorOrderIds[] = $vendorOrder->getId();
                $vendorTotalPayoutAmount += $payoutAmount;
                $vendorPayoutInvoiceData['orders_total_incl_tax'] += $vendorOrderTotalIncTax;
                $vendorPayoutInvoiceData['orders_total_tax'] += $vendorOrder->getBaseTaxAmount();
                $vendorPayoutInvoiceData['fees_total_incl_tax'] += $vendorOrderFeeTotalIncTax;
                $vendorPayoutInvoiceData['fees_total_tax'] += $vendorOrderTotalFeeTax;

            }

            /*
             * Integrate with assembly pay for send payout
             */
            $eventData = [
                'payout_ref' => $payoutRef,
                'account_ref' => $accountRef,
                'amount' => $vendorTotalPayoutAmount,
                'description' => 'Payout for ' . $vendorName,
                'custom_descriptor' => 'This will be displayed on vendor account transaction',
                'ext_info' => [
                    'vendor_id' => $vendorId,
                    'payout_id' => $payoutId,
                    'vendor_order_ids' => $vendorOrderIds,
                ],
            ];
            $this->_eventManager->dispatch('omnyfy_mcm_payout_send', ['data' => $eventData]);

            /**
             * Save vendor payout invoice
             */
            $vendorPayoutInvoiceData['total_earning_incl_tax'] = $vendorTotalPayoutAmount;
            $this->saveVendorPayoutInvoice($vendorPayoutInvoiceData, $vendorOrderCollection);
            $this->setLastRef($vendorPayoutInvoiceData['increment_id'], 'invoice_ref');
        }

        // get number of orders that are not going to be processed
        $vendorOrderCollectionNonStripe = $this->getVendorOrderCollection($vendorId);
        $vendorOrderCollectionNonStripe->getSelect()
            ->join(
                ['payment' => $vendorOrderCollection->getTable('sales_order_payment')],
                'payment.parent_id = main_table.order_id',
                ['method']
            )->where('method like ?', 'stripe_payments');

        if (!empty($vendorOrderCollectionNonStripe) && count ($vendorOrderCollectionNonStripe) > 0) {
            foreach ($vendorOrderCollectionNonStripe as $nonManualOrder) {
                $this->messageManager->addErrorMessage('Order: ' . $nonManualOrder->getOrderIncrementId() . ' could not be processed. This payout has been paid using Stripe and the Vendor has a Stripe payout type, please select Process Stripe Payouts to make this payment.');
            }
        }

        return $updatedOrderCount;
    }

    public function vendorPayoutsProcess($payout, $payoutRef)
    {
        try {
            $currentPayoutTypeId = false;
            if ($this->isProcessPayoutForStripe($payout->getVendorId())) {
                $vendorPayoutType = $this->vendorPayoutTypeCollection->create()->addFieldToFilter('vendor_id', ['eq' => $payout->getVendorId()])->getFirstItem();
                $currentPayoutTypeId = $vendorPayoutType->getPayoutTypeId();
                /** temporarily update Vendor Payout Type to Manual */
                $vendorPayoutType->setPayoutTypeId($this->getPayoutTypeId());
                $vendorPayoutType->save();
                $updatedOrderCount = $this->vendorManualPayoutsProcess($payout, $payoutRef);
                $vendorPayoutType->setPayoutTypeId($currentPayoutTypeId);
                $vendorPayoutType->save();
                return $updatedOrderCount;
            }
        } catch (\Exception $e) {
            if ($currentPayoutTypeId !== false) {
                $vendorPayoutType->setPayoutTypeId($currentPayoutTypeId);
                $vendorPayoutType->save();
            }
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
        return parent::vendorPayoutsProcess($payout, $payoutRef); // TODO: Change the autogenerated stub
    }
}
