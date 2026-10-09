<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\Stripe;

use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Generated\Shared\Transfer\StripePaymentDetailsRequestTransfer;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerEco\Client\Stripe\StripeFactory getFactory()
 */
class StripeClient extends AbstractClient implements StripeClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function processWebhook(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer {
        return $this->getFactory()->createZedStub()->processWebhook($webhookPayloadTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @deprecated Use {@link \SprykerEco\Client\Stripe\StripeClient::getPaymentDetailsByRequest()} instead.
     */
    public function getPaymentDetails(string $orderReference, ?string $customerReference = null): StripeIntentResponseTransfer
    {
        return $this->getFactory()->createZedStub()->getPaymentDetails($orderReference, $customerReference);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPaymentDetailsByRequest(
        StripePaymentDetailsRequestTransfer $stripePaymentDetailsRequestTransfer,
    ): StripeIntentResponseTransfer {
        return $this->getFactory()->createZedStub()->getPaymentDetailsByRequest($stripePaymentDetailsRequestTransfer);
    }
}
