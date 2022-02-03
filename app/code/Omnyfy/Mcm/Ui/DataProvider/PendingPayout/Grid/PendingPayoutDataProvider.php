<?php

namespace Omnyfy\Mcm\Ui\DataProvider\PendingPayout\Grid;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Magento\Framework\Api\Search\SearchResultInterface;
use Omnyfy\Mcm\Model\ResourceModel\VendorPayout;
use Omnyfy\Mcm\Helper\Data as HelperData;
use Omnyfy\Mcm\Model\ResourceModel\VendorPayoutType\CollectionFactory as VendorPayoutTypeCollection;

class PendingPayoutDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider {

    protected $pricing;

    protected $vendorPayoutTypeCollection;

    /**
     * @param string                $name
     * @param string                $primaryFieldName
     * @param string                $requestFieldName
     * @param Reporting             $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface      $request
     * @param FilterBuilder         $filterBuilder
     * @param array                 $meta
     * @param array                 $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
    $name, $primaryFieldName, $requestFieldName, Reporting $reporting, SearchCriteriaBuilder $searchCriteriaBuilder, RequestInterface $request, FilterBuilder $filterBuilder, \Magento\Framework\Pricing\Helper\Data $pricing, VendorPayout $vendorPayoutResource, HelperData $helper, VendorPayoutTypeCollection $vendorPayoutTypeCollection, array $meta = [], array $data = []
    ) {
        $this->pricing = $pricing;
        $this->vendorPayoutResource = $vendorPayoutResource;
        $this->_helper = $helper;
        $this->vendorPayoutTypeCollection = $vendorPayoutTypeCollection;
        parent::__construct(
                $name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data
        );
    }

    /**
     * @param SearchResultInterface $searchResult
     * @return array
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult) {
        $arrItems = [];
        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        $arrItems['items'] = [];
        foreach ($searchResult->getItems() as $item) {
            $arrItems['items'][] = $item->getData();
        }

        return $arrItems;
    }
    public function getSearchResult()
    {
        $collection = parent::getSearchResult(); // TODO: Change the autogenerated stub
        foreach ($collection as $payout) {
            $balanceOwing = $this->vendorPayoutResource->getTotalPayoutsPending($payout['vendor_id']);
            $payoutAmount = $this->vendorPayoutResource->getTotalReadyToPay($payout['vendor_id']);
            $vendorPayoutType = $this->vendorPayoutTypeCollection->create();
            $resource = $vendorPayoutType->getResource();
            $vendorPayoutType->addFieldToFilter('vendor_id', ['eq' => $payout->getVendorId()])
                ->join(
                    ['payout_type' => $resource->getTable('omnyfy_mcm_payout_type')],
                    'main_table.payout_type_id = payout_type.id',
                    ['payout_type']
                );
            $payout->setData('vendor_name_status', ($payout['vendor_name']) ? ($payout['vendor_status'] == 0 ? $payout['vendor_name'] . ' (Disabled)' : $payout['vendor_name']) : '');
            $payout->setData('balance_owing', $this->currency($balanceOwing['total_balance_owing']));
            $payout->setData('payout_amount', $this->currency($payoutAmount['total_payout_amount']));
            $payout->setData('payout_type', $vendorPayoutType->getFirstItem()->getPayoutType());

            //$payout->setData('vendor_name_status', ($payout['payout_amount']) ? $this->currency($payout['payout_amount'], true, false) : '');
        }
        return $collection;
    }
    
    public function currency($value) {
        return $this->_helper->formatToBaseCurrency($value);
    }

}
