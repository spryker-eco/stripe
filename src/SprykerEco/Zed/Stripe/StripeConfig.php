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
    public const string APP_NAME = 'Stripe';

    /**
     * Stripe API version pinned for this package.
     */
    public const string STRIPE_API_VERSION = '2023-10-16';

    // Metadata keys written to Stripe PaymentIntent metadata
    public const string METADATA_ORDER_REFERENCE = 'orderReference';

    public const string METADATA_MERCHANT_REFERENCE = 'merchantReference';

    public const string METADATA_MERCHANT_NAME = 'merchantName';

    public const string METADATA_ITEM_REFERENCES = 'itemReferences';

    // Merchant onboarding state names (mirrors Stripe account requirements_due / capabilities status)
    public const string ONBOARDING_STATUS_COMPLETED = 'completed';

    public const string ONBOARDING_STATUS_ENABLED = 'enabled';

    public const string ONBOARDING_STATUS_RESTRICTED = 'restricted';

    public const string ONBOARDING_STATUS_RESTRICTED_SOON = 'restricted soon';

    public const string ONBOARDING_STATUS_PENDING = 'pending';

    public const string ONBOARDING_STATUS_REJECTED = 'rejected';

    // Keys for the attributes array inside each MerchantOnboardingState
    public const string ONBOARDING_STATE_ATTR_STATUS_TEXT = 'statusText';

    public const string ONBOARDING_STATE_ATTR_DISPLAY_TEXT = 'displayText';

    public const string ONBOARDING_STATE_ATTR_BUTTON_TEXT = 'buttonText';

    public const string ONBOARDING_STATE_ATTR_BUTTON_INFO = 'buttonInfo';

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
     * Returns the merchant onboarding states that Stripe can place a connected account in,
     * along with the UI texts shown in the Merchant Portal for each state.
     * Override to customise labels without changing business logic.
     *
     * @api
     *
     * @return array<string, array<string, string>>
     */
    public function getMerchantOnboardingStates(): array
    {
        return [
            static::ONBOARDING_STATUS_COMPLETED => [
                static::ONBOARDING_STATE_ATTR_STATUS_TEXT => 'Completed',
                static::ONBOARDING_STATE_ATTR_DISPLAY_TEXT => 'Your %s account has been successfully connected to the Marketplace account. You can get your payout.',
                static::ONBOARDING_STATE_ATTR_BUTTON_TEXT => '',
                static::ONBOARDING_STATE_ATTR_BUTTON_INFO => 'You are connected to the Marketplace account',
            ],
            static::ONBOARDING_STATUS_ENABLED => [
                static::ONBOARDING_STATE_ATTR_STATUS_TEXT => 'Enabled',
                static::ONBOARDING_STATE_ATTR_DISPLAY_TEXT => 'Your %s account has been successfully connected to the Marketplace account. %s may require more information once transaction volume increases.',
                static::ONBOARDING_STATE_ATTR_BUTTON_TEXT => 'Update Profile',
                static::ONBOARDING_STATE_ATTR_BUTTON_INFO => 'You are connected to the Marketplace account',
            ],
            static::ONBOARDING_STATUS_RESTRICTED => [
                static::ONBOARDING_STATE_ATTR_STATUS_TEXT => 'Restricted',
                static::ONBOARDING_STATE_ATTR_DISPLAY_TEXT => 'You are required to provide more details for your %s account. This step is required so that your payouts are not paused.',
                static::ONBOARDING_STATE_ATTR_BUTTON_TEXT => 'Continue Onboarding',
                static::ONBOARDING_STATE_ATTR_BUTTON_INFO => 'You are required to provide more details to %s',
            ],
            static::ONBOARDING_STATUS_RESTRICTED_SOON => [
                static::ONBOARDING_STATE_ATTR_STATUS_TEXT => 'Restricted soon',
                static::ONBOARDING_STATE_ATTR_DISPLAY_TEXT => 'You are required to provide more details for your %s account. This step is required so that your payouts are not paused.',
                static::ONBOARDING_STATE_ATTR_BUTTON_TEXT => 'Continue Onboarding',
                static::ONBOARDING_STATE_ATTR_BUTTON_INFO => 'You are required to provide more details to %s',
            ],
            static::ONBOARDING_STATUS_PENDING => [
                static::ONBOARDING_STATE_ATTR_STATUS_TEXT => 'Pending',
                static::ONBOARDING_STATE_ATTR_DISPLAY_TEXT => 'Click the button below to get connected to the Marketplace account. This step is required for you to get your payout.',
                static::ONBOARDING_STATE_ATTR_BUTTON_TEXT => 'Continue Onboarding',
                static::ONBOARDING_STATE_ATTR_BUTTON_INFO => 'Connect to the Marketplace account',
            ],
            static::ONBOARDING_STATUS_REJECTED => [
                static::ONBOARDING_STATE_ATTR_STATUS_TEXT => 'Rejected',
                static::ONBOARDING_STATE_ATTR_DISPLAY_TEXT => 'Your %s account has been rejected by the Marketplace. Payouts are paused. Please contact the Marketplace to resolve this.',
                static::ONBOARDING_STATE_ATTR_BUTTON_TEXT => '',
                static::ONBOARDING_STATE_ATTR_BUTTON_INFO => 'Your %s account is disabled from the Marketplace account',
            ],
        ];
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
