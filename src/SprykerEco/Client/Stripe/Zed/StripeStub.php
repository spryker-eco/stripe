<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\Stripe\Zed;

use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

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
