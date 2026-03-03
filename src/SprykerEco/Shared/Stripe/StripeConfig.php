<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Shared\Stripe;

class StripeConfig
{
    public const string PAYMENT_PROVIDER_NAME = 'stripe';

    public const string PAYMENT_METHOD_NAME = 'stripe';

    // Payment lifecycle status constants — used by WebhookHandler to select the correct
    // PaymentApp typed transfer (e.g. PaymentAuthorizedTransfer, PaymentCapturedTransfer).
    // OMS condition plugins are NOT defined here; they are owned by the PaymentApp module.
    public const string PAYMENT_STATUS_NEW = 'new';

    public const string PAYMENT_STATUS_AUTHORIZED = 'authorized';

    public const string PAYMENT_STATUS_AUTHORIZATION_FAILED = 'authorization_failed';

    public const string PAYMENT_STATUS_CAPTURED = 'captured';

    public const string PAYMENT_STATUS_CAPTURE_FAILED = 'capture_failed';

    public const string PAYMENT_STATUS_CAPTURE_REQUESTED = 'capture_requested';

    public const string PAYMENT_STATUS_CANCELED = 'canceled';

    public const string PAYMENT_STATUS_CANCELLATION_FAILED = 'cancellation_failed';

    public const string PAYMENT_STATUS_REFUNDED = 'refunded';

    public const string PAYMENT_STATUS_REFUND_FAILED = 'refund_failed';

    // Business model values
    public const string BUSINESS_MODEL_DIRECT = 'direct';

    public const string BUSINESS_MODEL_MARKETPLACE = 'marketplace';

    // Yves webhook route name (registered by StripeRouteProviderPlugin)
    public const string WEBHOOK_ROUTE = 'stripe-webhook';
}
