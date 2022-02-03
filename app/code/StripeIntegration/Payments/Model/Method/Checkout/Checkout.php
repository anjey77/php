<?php

namespace StripeIntegration\Payments\Model\Method\Checkout;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use StripeIntegration\Payments\Helper;
use StripeIntegration\Payments\Helper\Logger;
use Magento\Framework\Exception\CouldNotSaveException;

abstract class Checkout extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $type = '';
    protected $_code = '';
    // protected $_formBlockType = 'StripeIntegration\Payments\Block\Method\Checkout';
    protected $_infoBlockType = 'StripeIntegration\Payments\Block\Info';

    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canCaptureOnce = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_isGateway = true;
    protected $_isInitializeNeeded = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canUseForMultishipping  = false;
    protected $_canCancelInvoice = true;
    protected $_canUseCheckout = true;
    protected $_canSaveCc = false;

    protected $stripeCustomer = null;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Helper\Api $api,
        \StripeIntegration\Payments\Model\PaymentIntent $paymentIntent,
        \StripeIntegration\Payments\Model\Stripe\CouponFactory $couponFactory,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->cache = $context->getCacheManager();
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;

        $this->config = $config;
        $this->helper = $helper;
        $this->api = $api;
        $this->paymentIntent = $paymentIntent;
        $this->customer = $helper->getCustomerModel();
        $this->logger = $logger;
        $this->request = $request;
        $this->checkoutHelper = $checkoutHelper;
        $this->scopeConfig = $scopeConfig;
        $this->couponFactory = $couponFactory;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->config->initStripe())
            return false;

        if (parent::isAvailable($quote) === false) {
            return false;
        }

        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        if (!$quote) {
            return false;
        }

        return true;
    }

    public function adjustParamsForMethod(&$params, $payment, $order, $quote)
    {
        // Overwrite this method to specify custom params for this method
    }

    public function reset()
    {
        $this->stripeCustomer = null;
        $session = $this->checkoutHelper->getCheckout();
        $session->setStripePaymentsCheckoutSessionId(null);
    }

    protected function cleanPaymentIntentData($data)
    {
        $supportedParams = ['application_fee_amount', 'capture_method', 'description', 'metadata', 'on_behalf_of', 'receipt_email', 'setup_future_usage', 'shipping', 'statement_descriptor', 'statement_descriptor_suffix', 'transfer_data', 'transfer_group'];

        $params = [];

        foreach ($data as $key => $value)
            if (in_array($key, $supportedParams))
                $params[$key] = $value;

        return $params;
    }

    public function getLineItems($order)
    {
        $currency = $order->getOrderCurrencyCode();
        $cents = $this->helper->isZeroDecimal($currency) ? 1 : 100;
        $orderItems = $order->getAllVisibleItems();
        $lines = [];

        foreach ($orderItems as $orderItem)
        {
            // Skip configurable products
            if ($orderItem->getParentItem())
                continue;

            $product = $this->helper->loadProductById($orderItem->getProductId());

            $lines[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $orderItem->getName(),
                        'images' => [$this->helper->getProductImage($product)]
                    ],
                    'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount($orderItem->getPrice(), $currency)
                ],
                'quantity' => $orderItem->getQtyOrdered()
            ];
        }

        if ($order->getTaxAmount() > 0)
        {
            $lines[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => __("Tax")
                    ],
                    'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount($order->getTaxAmount(), $currency)
                ],
                'quantity' => 1
            ];
        }

        if ($order->getShippingAmount() > 0)
        {
            $lines[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => __("Shipping")
                    ],
                    'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount($order->getShippingAmount(), $currency)
                ],
                'quantity' => 1
            ];
        }

        return $lines;
    }

    protected function getSessionParams($order)
    {
        $returnUrl = $this->urlBuilder->getUrl('stripe/payment/index', [
            '_secure' => $this->request->isSecure(),
            'payment_method' => $this->type
        ]);

        $amount = $order->getGrandTotal();
        $currency = $order->getOrderCurrencyCode();
        $lineItems = $this->getLineItems($order);

        $params = [
            'cancel_url' => $returnUrl,
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'success_url' => $returnUrl,
            'client_reference_id' => $order->getIncrementId(),
            'metadata' => [
                'Order #' => $order->getIncrementId()
            ],
            'locale' => $this->config->getCheckoutLocale(),
            'payment_intent_data' => $this->cleanPaymentIntentData($this->paymentIntent->getParamsFrom($order->getQuote(), $order)),
            'submit_type' => 'pay',
            'line_items' => $lineItems
        ];

        if ($order->getDiscountAmount() < 0)
        {
            $couponId = $this->couponFactory->create()->fromOrder($order)->getId();
            $params['discounts'] = [['coupon' => $couponId]];
        }

        if ($this->config->getSaveCards())
        {
            try
            {
                $this->customer->createStripeCustomerIfNotExists(false, $order);
                $this->stripeCustomer = $this->customer->retrieveByStripeID();
                $params['customer'] = $this->stripeCustomer->id;
            }
            catch (\Stripe\Exception\CardException $e)
            {
                throw new LocalizedException(__($e->getMessage()));
            }
            catch (\Exception $e)
            {
                $this->helper->dieWithError(__('An error has occurred. Please contact us to complete your order.'), $e);
            }
        }
        else
        {
            $params['customer_email'] = $order->getCustomerEmail();
        }

        return $params;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $session = $this->checkoutHelper->getCheckout();
        $info = $this->getInfoInstance();
        $this->order = $order = $info->getOrder();
        $quote = $this->helper->getQuote();
        $this->reset();

        // We don't want to send an order email until the payment is collected asynchronously
        $order->setCanSendNewEmailFlag(false);

        $params = $this->getSessionParams($order);

        $this->adjustParamsForMethod($params, $info, $order, $quote);

        try {
            $checkoutSession = $this->config->getStripeClient()->checkout->sessions->create($params);
            $info->setAdditionalInformation("checkout_session_id", $checkoutSession->id);
            $session->setStripePaymentsCheckoutSessionId($checkoutSession->id);
            $order->addStatusHistoryComment(__("The customer was redirected for payment processing. The payment is pending."));
            $order->getPayment()
                ->setIsTransactionClosed(0)
                ->setIsTransactionPending(true);
        }
        catch (\Stripe\Exception\CardException $e)
        {
            throw new LocalizedException(__($e->getMessage()));
        }
        catch (\Exception $e)
        {
            if (strstr($e->getMessage(), 'Invalid country') !== false) {
                throw new LocalizedException(__('Sorry, this payment method is not available in your country.'));
            }
            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$payment->getLastTransId())
            throw new LocalizedException(__('Sorry, it is not possible to invoice this order because the payment is still pending.'));

        try
        {
            $this->helper->capture($payment->getLastTransId(), $payment, $amount, $this->config->retryWithSavedCard());
        }
        catch (\Exception $e)
        {
            $this->helper->dieWithError($e->getMessage());
        }

        return parent::capture($payment, $amount);
    }

    public function cancel(InfoInterface $payment, $amount = null)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $helper = $objectManager->get('Magento\Payment\Helper\Data');
        $method = $helper->getMethodInstance('stripe_payments');

        $method->cancel($payment, $amount);

        return $this;
    }

    public function refund(InfoInterface $payment, $amount)
    {
        $this->cancel($payment, $amount);
        return $this;
    }

    public function void(InfoInterface $payment)
    {
        $this->cancel($payment);
        return $this;
    }

    // Fixes https://github.com/magento/magento2/issues/5413 in Magento 2.1
    public function setId($code) { }
    public function getId() { return $this->_code; }
}
