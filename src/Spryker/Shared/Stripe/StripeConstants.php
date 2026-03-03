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
    public const string API_KEY = 'STRIPE:API_KEY';

    public const string API_SECRET = 'STRIPE:API_SECRET';

    public const string API_BASE_URL = 'STRIPE:API_BASE_URL';

    public const string API_TIMEOUT = 'STRIPE:API_TIMEOUT';

    public const string API_AUTHORIZE_PATH = 'STRIPE:API_AUTHORIZE_PATH';

    public const string API_CAPTURE_PATH = 'STRIPE:API_CAPTURE_PATH';

    public const string API_CANCEL_PATH = 'STRIPE:API_CANCEL_PATH';

    public const string API_PAYMENT_METHODS_PATH = 'STRIPE:API_PAYMENT_METHODS_PATH';
}
