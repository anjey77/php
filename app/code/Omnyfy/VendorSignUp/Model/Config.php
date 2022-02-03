<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Omnyfy\VendorSignUp\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MSP\ReCaptcha\Model\Config as ReCaptchaConfig;

/**
 * Configuration
 */
class Config
{
    /**
     * Enables reCaptcha on PayPal PayflowPro payment form.
     */
    const SIGNUP_GOOGLE_RECAPTCHA_ENABLED = 'msp_securitysuite_recaptcha/frontend/enabled_vendor_signup';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ReCaptchaConfig
     */
    private $reCaptchaConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ReCaptchaConfig $reCaptchaConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ReCaptchaConfig $reCaptchaConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->reCaptchaConfig = $reCaptchaConfig;
    }

    /**
     * Return true if enabled on frontend Vendor Sign Up form.
     *
     * @return bool
     */
    public function isEnabledFrontendVendorSignUp()
    {
        if (!$this->reCaptchaConfig->isEnabledFrontend()) {
            return false;
        }

        return (bool) $this->scopeConfig->getValue(static::SIGNUP_GOOGLE_RECAPTCHA_ENABLED);
    }
}
