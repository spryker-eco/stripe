<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe\Zed;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use SprykerEco\Client\ZedRequest\ZedRequestClientInterface;

class StripeStub implements StripeStubInterface
{
    public function __construct(
        protected ZedRequestClientInterface $zedRequestClient,
    ) {
    }

    public function processWebhook(
        StripeWebhookPayloadTransfer $stripeWebhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer {
        /** @var \Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer $stripeWebhookProcessResponseTransfer */
        $stripeWebhookProcessResponseTransfer = $this->zedRequestClient->call(
            '/stripe/gateway/process-webhook',
            $stripeWebhookPayloadTransfer,
        );

        return $stripeWebhookProcessResponseTransfer;
    }
}
