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

    public const string ONBOARDING_STRATEGY_REDIRECT = 'redirect';

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

    public const string PAYMENT_STATUS_SUCCEEDED = 'succeeded';

    public const string PAYMENT_STATUS_REQUIRES_CAPTURE = 'requires_capture';

    public const string PAYMENT_STATUS_PROCESSING = 'processing';

    public const string PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD = 'requires_payment_method';

    public const string PAYMENT_STATUS_REQUIRES_CONFIRMATION = 'requires_confirmation';

    public const string PAYMENT_STATUS_REQUIRES_ACTION = 'requires_action';

    /**
     * Specification:
     * - Returns a list of payment statuses that are considered successful.
     *
     * @api
     *
     * @return list<string>
     */
    public function getSuccessfulPaymentStatuses(): array
    {
        return [
            static::PAYMENT_STATUS_SUCCEEDED,
            static::PAYMENT_STATUS_REQUIRES_CAPTURE,
        ];
    }

    /**
     * Specification:
     * - Returns a list of payment statuses for which cancellation is not allowed.
     *
     * @api
     *
     * @return list<string>
     */
    public function getPaymentIntentNonCancellableStatuses(): array
    {
        return [
            static::PAYMENT_STATUS_SUCCEEDED,
            static::PAYMENT_STATUS_CAPTURED,
        ];
    }

    /**
     * Specification:
     * - Returns a list of payment statuses for which cancellation is allowed.
     *
     * @api
     *
     * @return list<string>
     */
    public function getPaymentIntentCancellableStatuses(): array
    {
        return [
            static::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD,
            static::PAYMENT_STATUS_REQUIRES_CAPTURE,
            static::PAYMENT_STATUS_REQUIRES_CONFIRMATION,
            static::PAYMENT_STATUS_REQUIRES_ACTION,
        ];
    }

    /**
     * Specification:
     * - Returns a list of payment statuses for which the client_secret can be reused to collect payment.
     *
     * @api
     *
     * @return list<string>
     */
    public function getReusablePaymentStatuses(): array
    {
        return [
            static::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD,
            static::PAYMENT_STATUS_REQUIRES_ACTION,
            static::PAYMENT_STATUS_PROCESSING,
        ];
    }
}
