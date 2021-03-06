<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Omnyfy\Vendor\Model\Quote\Address\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address\FreeShippingInterface;

class Shipping extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var FreeShippingInterface
     */
    protected $freeShipping;


    protected $helper;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param FreeShippingInterface $freeShipping
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        FreeShippingInterface $freeShipping,
        \Omnyfy\Vendor\Helper\Data $helper
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->freeShipping = $freeShipping;
        $this->helper = $helper;
        $this->setCode('shipping');
    }

    /**
     * Collect totals information about shipping
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $address = $shippingAssignment->getShipping()->getAddress();
        $method = $shippingAssignment->getShipping()->getMethod();

        $address->setWeight(0);
        $address->setFreeMethodWeight(0);
        $address->setFreeShipping(false);

        $addressWeight = $address->getWeight();
        $freeMethodWeight = $address->getFreeMethodWeight();

        $isAllFree = $this->freeShipping->isFreeShipping($quote, $shippingAssignment->getItems());
        if ($isAllFree && !$address->getFreeShipping()) {
            $address->setFreeShipping(true);
        }
        $total->setTotalAmount($this->getCode(), 0);
        $total->setBaseTotalAmount($this->getCode(), 0);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $addressQty = 0;
        foreach ($shippingAssignment->getItems() as $item) {
            /**
             * Skip if this item is virtual
             */
            if ($item->getProduct()->isVirtual()) {
                continue;
            }

            /**
             * Children weight we calculate for parent
             */
            if ($item->getParentItem()) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if ($child->getProduct()->isVirtual()) {
                        continue;
                    }
                    $addressQty += $child->getTotalQty();

                    if (!$item->getProduct()->getWeightType()) {
                        $itemWeight = $child->getWeight();
                        $itemQty = $child->getTotalQty();
                        $rowWeight = $itemWeight * $itemQty;
                        $addressWeight += $rowWeight;
                        if ($address->getFreeShipping() || $child->getFreeShipping() === true) {
                            $rowWeight = 0;
                        } elseif (is_numeric($child->getFreeShipping())) {
                            $freeQty = $child->getFreeShipping();
                            if ($itemQty > $freeQty) {
                                $rowWeight = $itemWeight * ($itemQty - $freeQty);
                            } else {
                                $rowWeight = 0;
                            }
                        }
                        $freeMethodWeight += $rowWeight;
                        $item->setRowWeight($rowWeight);
                    }
                }
                if ($item->getProduct()->getWeightType()) {
                    $itemWeight = $item->getWeight();
                    $rowWeight = $itemWeight * $item->getQty();
                    $addressWeight += $rowWeight;
                    if ($address->getFreeShipping() || $item->getFreeShipping() === true) {
                        $rowWeight = 0;
                    } elseif (is_numeric($item->getFreeShipping())) {
                        $freeQty = $item->getFreeShipping();
                        if ($item->getQty() > $freeQty) {
                            $rowWeight = $itemWeight * ($item->getQty() - $freeQty);
                        } else {
                            $rowWeight = 0;
                        }
                    }
                    $freeMethodWeight += $rowWeight;
                    $item->setRowWeight($rowWeight);
                }
            } else {
                if (!$item->getProduct()->isVirtual()) {
                    $addressQty += $item->getQty();
                }
                $itemWeight = $item->getWeight();
                $rowWeight = $itemWeight * $item->getQty();
                $addressWeight += $rowWeight;
                if ($address->getFreeShipping() || $item->getFreeShipping() === true) {
                    $rowWeight = 0;
                } elseif (is_numeric($item->getFreeShipping())) {
                    $freeQty = $item->getFreeShipping();
                    if ($item->getQty() > $freeQty) {
                        $rowWeight = $itemWeight * ($item->getQty() - $freeQty);
                    } else {
                        $rowWeight = 0;
                    }
                }
                $freeMethodWeight += $rowWeight;
                $item->setRowWeight($rowWeight);
            }
        }

        if (isset($addressQty)) {
            $address->setItemQty($addressQty);
        }

        $address->setWeight($addressWeight);
        $address->setFreeMethodWeight($freeMethodWeight);
        $address->collectShippingRates();

        if ($method) {
            $locationIds = $this->helper->getLocationIds($quote);
            $methods = $this->helper->shippingMethodStringToArray($method);

            if (!empty($methods)) {
                $shippingDescription = '';
                $totalAmount = $baseTotal = $shippingAmount = $baseShippingAmount = 0;
                $allRates = $address->getAllShippingRates();
                foreach ($locationIds as $locationId) {
                    foreach ($allRates as $rate) {
                        if ($locationId == $rate->getLocationId()
                            && array_key_exists($locationId, $methods)
                            && $rate->getCode() == $methods[$locationId]
                        ) {
                            $store = $quote->getStore();
                            $amountPrice = $this->priceCurrency->convert(
                                $rate->getPrice(),
                                $store
                            );
                            $totalAmount += $amountPrice;
                            $baseTotal += $rate->getPrice();
                            $baseShippingAmount += $rate->getPrice();
                            $shippingAmount += $amountPrice;

                            $shippingDescription .= (empty($shippingDescription) ? '': "\n") . $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                            break;
                        }
                    }
                }
                $total->setTotalAmount($this->getCode(), $totalAmount);
                $total->setBaseTotalAmount($this->getCode(), $baseTotal);
                $total->setBaseShippingAmount($baseShippingAmount);
                $total->setShippingAmount($shippingAmount);
                $address->setShippingDescription(trim($shippingDescription, ' -'));
                $total->setShippingDescription($address->getShippingDescription());
            }
        }
        return $this;
    }

    /**
     * Add shipping totals information to address object
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $amount = $total->getShippingAmount();
        $shippingDescription = $total->getShippingDescription();
        $title = ($amount != 0 && $shippingDescription)
            ? __('Shipping & Handling (%1)', $shippingDescription)
            : __('Shipping & Handling');

        return [
            'code' => $this->getCode(),
            'title' => $title,
            'value' => $amount
        ];
    }

    /**
     * Get Shipping label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Shipping');
    }
}
