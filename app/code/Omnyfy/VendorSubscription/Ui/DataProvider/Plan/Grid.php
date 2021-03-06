<?php
/**
 * Project: Vendor Subscription
 * User: jing
 * Date: 2019-07-04
 * Time: 10:55
 */
namespace Omnyfy\VendorSubscription\Ui\DataProvider\Plan;

use Magento\Framework\Api\Search\SearchResultInterface;

class Grid extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems = [];
        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        $arrItems['items'] = [];
        foreach ($searchResult->getItems() as $item) {
            $arrItems['items'][] = $item->getData();
        }

        return $arrItems;
    }
}
 