<?php

namespace StripeIntegration\Payments\Model\Method\Checkout;

class Card extends \StripeIntegration\Payments\Model\Method\Checkout\Checkout
{
    const METHOD_CODE = 'stripe_payments_checkout_card';

    protected $_code = self::METHOD_CODE;

    protected $type = 'checkout_card';

    public function getTitle()
    {
        return $this->config->getConfigData("title");
    }

    public function isEnabled($quote)
    {
        return ($this->config->isEnabled() &&
            $this->config->getConfigData("checkout_mode") == 1) &&
            !$this->helper->isAdmin() &&
            !$this->helper->isMultiShipping() &&
            !$this->helper->hasSubscriptions($quote);
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->isEnabled($quote))
            return false;

        return parent::isAvailable($quote);
    }
}
