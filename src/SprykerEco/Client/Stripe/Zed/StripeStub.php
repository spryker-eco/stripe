<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\Stripe\Zed;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Generated\Shared\Transfer\StripePaymentDetailsRequestTransfer;
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

    public function getPaymentDetails(string $orderReference, ?string $customerReference): StripeIntentResponseTransfer
    {
        /** @var \Generated\Shared\Transfer\StripeIntentResponseTransfer $stripeIntentResponseTransfer */
        $stripeIntentResponseTransfer = $this->zedRequestClient->call(
            '/stripe/gateway/get-payment-details',
            (new OrderTransfer())
                ->setOrderReference($orderReference)
                ->setCustomerReference($customerReference),
        );

        return $stripeIntentResponseTransfer;
    }

    public function getPaymentDetailsByRequest(
        StripePaymentDetailsRequestTransfer $stripePaymentDetailsRequestTransfer,
    ): StripeIntentResponseTransfer {
        /** @var \Generated\Shared\Transfer\StripeIntentResponseTransfer $stripeIntentResponseTransfer */
        $stripeIntentResponseTransfer = $this->zedRequestClient->call(
            '/stripe/gateway/get-payment-details-by-request',
            $stripePaymentDetailsRequestTransfer,
        );

        return $stripeIntentResponseTransfer;
    }
}
