<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Shared\Stripe;

class StripeConfig
{
    public const string PAYMENT_PROVIDER_NAME = 'Stripe';

    public const string PAYMENT_METHOD_NAME = 'stripe';

    public const string ROUTE_PATH_PAYMENT = '/stripe/payment';

    public const string ROUTE_PATH_NOTIFICATION = '/stripe/notification';

    public const string ONBOARDING_TYPE = 'payment';

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

    public const string PAYMENT_STATUS_PARTIALLY_REFUNDED = 'partially refunded';

    public const string PAYMENT_STATUS_REFUND_FAILED = 'refund failed';
}
