<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
