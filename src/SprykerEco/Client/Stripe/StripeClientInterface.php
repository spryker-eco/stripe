<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\Stripe;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;

interface StripeClientInterface
{
    /**
     * Specification:
     * - Forwards the raw webhook payload and Stripe-Signature header to Zed for processing.
     * - Zed verifies the signature, parses the event, and writes the payment status.
     * - Returns a response indicating success or failure.
     *
     * @api
     */
    public function processWebhook(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer;
}
