<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business\Webhook;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;

class WebhookHandler implements WebhookHandlerInterface
{
    public function processWebhook(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer {
        // TODO: implement in Phase 8 (WebhookHandler migration)
        return new StripeWebhookProcessResponseTransfer();
    }
}
