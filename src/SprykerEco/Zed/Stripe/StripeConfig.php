<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe;

use Spryker\Zed\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\Stripe\StripeConstants;
use Stripe\Event;

class StripeConfig extends AbstractBundleConfig
{
    /**
     * Stripe API version pinned for this package.
     */
    public const string STRIPE_API_VERSION = '2023-10-16';

    // Metadata keys written to Stripe PaymentIntent metadata
    public const string METADATA_ORDER_REFERENCE = 'orderReference';

    public const string METADATA_MERCHANT_REFERENCE = 'merchantReference';

    public const string METADATA_MERCHANT_NAME = 'merchantName';

    public const string METADATA_ITEM_REFERENCES = 'itemReferences';

    /**
     * @api
     */
    public function getSecretKey(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_SECRET_KEY, '');
    }

    /**
     * @api
     */
    public function getPublishableKey(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_PUBLISHABLE_KEY, '');
    }

    /**
     * @api
     */
    public function getWebhookSecret(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_WEBHOOK_SECRET, '');
    }

    /**
     * @api
     *
     * @return string 'direct'|'marketplace'
     */
    public function getBusinessModel(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_BUSINESS_MODEL, 'direct');
    }

    /**
     * @api
     *
     * @return string 'test'|'live'
     */
    public function getEnvironment(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_ENVIRONMENT, 'test');
    }

    // Marketplace-only methods

    /**
     * @api
     */
    public function getAccountId(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_ACCOUNT_ID, '');
    }

    /**
     * @api
     */
    public function getConnectedAccountWebhookSecret(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_WEBHOOK_SECRET_CONNECT, '');
    }

    // Marketplace: Merchant onboarding URL helpers

    /**
     * Full URL of the OnboardingController::initializeAction endpoint.
     * Registered via StripeMarketplaceInstallerPlugin so MerchantApp can POST to it.
     *
     * @api
     */
    public function getMerchantOnboardingInitializeUrl(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_MERCHANT_ONBOARDING_INITIALIZE_URL, '');
    }

    /**
     * Stripe Connect account link return_url (fallback when not provided by the request).
     *
     * @api
     */
    public function getMerchantOnboardingReturnUrl(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_MERCHANT_ONBOARDING_RETURN_URL, '');
    }

    /**
     * Stripe Connect account link refresh_url (fallback when not provided by the request).
     *
     * @api
     */
    public function getMerchantOnboardingRefreshUrl(): string
    {
        return (string)$this->get(StripeConstants::STRIPE_MERCHANT_ONBOARDING_REFRESH_URL, '');
    }

    /**
     * App identifier registered with MerchantApp module.
     *
     * @api
     */
    public function getMerchantOnboardingAppIdentifier(): string
    {
        return 'stripe';
    }

    /**
     * Onboarding type registered with MerchantApp module.
     *
     * @api
     */
    public function getMerchantOnboardingType(): string
    {
        return 'payment';
    }

    /**
     * Returns the Stripe webhook event types this package handles.
     * Register these in the Stripe Dashboard for your webhook endpoint.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedWebhookEvents(): array
    {
        return [
            Event::PAYMENT_INTENT_AMOUNT_CAPTURABLE_UPDATED, // → authorized
            Event::PAYMENT_INTENT_SUCCEEDED, // → captured
            Event::PAYMENT_INTENT_PAYMENT_FAILED, // → capture_failed / keep new (3DS retry)
            Event::PAYMENT_INTENT_CANCELED, // → canceled
            Event::CHARGE_FAILED, // → capture_failed (captured=true)
            Event::CHARGE_REFUND_UPDATED, // → refunded / refund_failed
            Event::ACCOUNT_UPDATED, // marketplace: merchant onboarding status
        ];
    }
}
