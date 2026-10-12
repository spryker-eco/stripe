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

    /**
     * Specification:
     * - Makes Zed request.
     * - Returns `isSuccessful=false` without any payment data when `$customerReference` is null or empty, or when the order does not belong to that customer.
     * - Fetches clientSecret live from Stripe API and publishableKey from config for the given order.
     *
     * @api
     *
     * @deprecated Use {@link \SprykerEco\Client\Stripe\StripeClientInterface::getPaymentDetailsByRequest()} instead.
     */
    public function getPaymentDetails(string $orderReference, ?string $customerReference = null): StripeIntentResponseTransfer;

    /**
     * Specification:
     * - Makes Zed request.
     * - Grants access when `StripePaymentDetailsRequestTransfer.customerReference` owns the order.
     * - Grants access when `StripePaymentDetailsRequestTransfer.isOrderPlacedInCurrentSession` is true. Zed does not verify the flag, set it only when the current session quote placed the order.
     * - Grants access when `StripePaymentDetailsRequestTransfer.clientSecret` is the client secret of the order's PaymentIntent.
     * - Returns `isSuccessful=false` without any payment data when no access condition is met.
     * - Fetches clientSecret live from Stripe API and publishableKey from config for the given order.
     * - Used by the Yves Stripe payment page to mount Stripe Elements.
     *
     * @api
     */
    public function getPaymentDetailsByRequest(
        StripePaymentDetailsRequestTransfer $stripePaymentDetailsRequestTransfer,
    ): StripeIntentResponseTransfer;
}
