<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Zed;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;

interface StripeStubInterface
{
    public function processWebhook(
        StripeWebhookPayloadTransfer $stripeWebhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer;
}
