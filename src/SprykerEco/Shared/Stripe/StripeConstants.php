<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Shared\Stripe;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface StripeConstants
{
    // Stripe API credentials
    public const string STRIPE_SECRET_KEY = 'STRIPE:STRIPE_SECRET_KEY';

    public const string STRIPE_PUBLISHABLE_KEY = 'STRIPE:STRIPE_PUBLISHABLE_KEY';

    public const string STRIPE_WEBHOOK_SECRET = 'STRIPE:STRIPE_WEBHOOK_SECRET';

    // Marketplace (optional)
    public const string STRIPE_ACCOUNT_ID = 'STRIPE:STRIPE_ACCOUNT_ID';

    public const string STRIPE_WEBHOOK_SECRET_CONNECT = 'STRIPE:STRIPE_WEBHOOK_SECRET_CONNECT';

    // Business model: 'direct'|'marketplace'
    public const string STRIPE_BUSINESS_MODEL = 'STRIPE:STRIPE_BUSINESS_MODEL';

    // Environment: 'test'|'live'
    public const string STRIPE_ENVIRONMENT = 'STRIPE:STRIPE_ENVIRONMENT';
}
