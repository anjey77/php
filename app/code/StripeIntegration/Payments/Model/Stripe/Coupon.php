<?php

namespace StripeIntegration\Payments\Model\Stripe;

class Coupon extends StripeObject
{
    protected $objectSpace = 'coupons';

    public function fromOrder($order)
    {
        $currency = $order->getOrderCurrencyCode();
        $discount = abs($order->getDiscountAmount());
        $amount = $this->helper->convertMagentoAmountToStripeAmount($discount, $currency);
        if (!$amount || $amount <= 0)
            return $this;

        $name = $this->helper->addCurrencySymbol($discount, $currency) . " Discount";
        $couponId = ((string)$amount) . strtoupper($currency);

        $data = [
            'duration' => 'once',
            'amount_off' => $amount,
            'currency' => $currency,
            'name' => $name
        ];

        $this->getObject($couponId);

        if (!$this->object)
        {
            $data["id"] = $couponId;
            $this->createObject($data);
        }

        if (!$this->object)
            throw new \Magento\Framework\Exception\LocalizedException(__("The discount for order #%1 could not be created in Stripe", $order->getIncrementId()));

        return $this;
    }
}
