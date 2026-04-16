<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Shared\Stripe;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class StripeConfig extends AbstractSharedConfig
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
     *  - List of payment statuses that are considered successful.
     *
     * @api
     *
     * @var list<string>
     */
    public const array SUCCESSFUL_PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_SUCCEEDED,
        self::PAYMENT_STATUS_REQUIRES_CAPTURE,
    ];

    /**
     * Specification:
     *  - List of payment statuses for which cancellation is not allowed.
     *
     * @api
     *
     * @var list<string>
     */
    public const array PAYMENT_INTENT_NON_CANCELLABLE_PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_SUCCEEDED,
        self::PAYMENT_STATUS_CAPTURED,
    ];

    /**
     * Specification:
     *  - List of payment statuses for which cancellation is allowed.
     *
     * @api
     *
     * @var list<string>
     */
    public const array PAYMENT_INTENT_CANCELLABLE_PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD,
        self::PAYMENT_STATUS_REQUIRES_CAPTURE,
        self::PAYMENT_STATUS_REQUIRES_CONFIRMATION,
        self::PAYMENT_STATUS_REQUIRES_ACTION,
    ];

    /**
     * Specification:
     *  - List of payment statuses for which the client_secret can be reused to collect payment.
     *
     * @api
     *
     * @var list<string>
     */
    public const array REUSABLE_PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD,
        self::PAYMENT_STATUS_REQUIRES_ACTION,
        self::PAYMENT_STATUS_PROCESSING,
    ];

    public const string CONFIGURATION_KEY_STRIPE_SECRET_KEY = 'integrations:stripe:credentials:secret_key';

    public const string CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY = 'integrations:stripe:credentials:publishable_key';

    public const string CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET = 'integrations:stripe:credentials:webhook_secret';

    public const string CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT = 'integrations:stripe:credentials:webhook_secret_connect';

    /**
     * Specification:
     * - Returns whether the Configuration module is used for Stripe configuration.
     * - When enabled, configuration values are retrieved from the Configuration module.
     * - When disabled, configuration values are retrieved from static Shared config.
     *
     * @api
     */
    public function isConfigurationModuleUsed(): bool
    {
        return false;
    }
}
