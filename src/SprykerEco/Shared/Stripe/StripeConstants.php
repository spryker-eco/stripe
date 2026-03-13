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
}
