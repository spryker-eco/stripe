<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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

    // Marketplace: Merchant Connect onboarding URLs
    public const string STRIPE_MERCHANT_ONBOARDING_INITIALIZE_URL = 'STRIPE:STRIPE_MERCHANT_ONBOARDING_INITIALIZE_URL';

    public const string STRIPE_MERCHANT_ONBOARDING_RETURN_URL = 'STRIPE:STRIPE_MERCHANT_ONBOARDING_RETURN_URL';

    public const string STRIPE_MERCHANT_ONBOARDING_REFRESH_URL = 'STRIPE:STRIPE_MERCHANT_ONBOARDING_REFRESH_URL';
}
