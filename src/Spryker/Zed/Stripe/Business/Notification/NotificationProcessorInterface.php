<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business\Notification;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;

interface NotificationProcessorInterface
{
    public function processWebhook(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer
    ): StripeWebhookProcessResponseTransfer;
}
