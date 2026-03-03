<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe;

use SprykerEco\Client\Kernel\AbstractBundleConfig;

class StripeConfig extends AbstractBundleConfig
{
    /**
     * Zed gateway URL for processing Stripe webhooks via ZedRequest.
     * Routes to GatewayController::processWebhookAction().
     */
    public const string ZED_PROCESS_WEBHOOK_URL = '/stripe/gateway/process-webhook';
}
