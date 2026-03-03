<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentMethodsTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;

interface StripeFacadeInterface
{
    /**
     * Specification:
     * - Verifies Stripe webhook signature.
     * - Parses the event and writes the corresponding status to `spy_payment_app_payment_status` via PaymentAppFacade.
     * - For `account.updated` (marketplace): delegates to MerchantOnboardingHandler.
     *
     * @api
     */
    public function processWebhook(StripeWebhookPayloadTransfer $webhookPayloadTransfer): StripeWebhookProcessResponseTransfer;

    /**
     * Specification:
     * - Creates a Stripe PaymentIntent for the given quote.
     * - Returns the client secret and transaction ID needed by Stripe Elements JS.
     *
     * @api
     */
    public function initializePayment(QuoteTransfer $quoteTransfer): StripeIntentResponseTransfer;

    /**
     * Specification:
     * - Persists a `spy_stripe_payment` record linking the order to the Stripe PaymentIntent.
     * - Called from StripeCheckoutDoSaveOrderPlugin during order save.
     *
     * @api
     */
    public function savePayment(QuoteTransfer $quoteTransfer, SaveOrderTransfer $saveOrderTransfer): void;

    /**
     * Specification:
     * - Verifies the Stripe PaymentIntent is in an authorized state.
     * - Authorization itself happens client-side; this step confirms the result.
     * - Called from StripeAuthorizeCommandPlugin or StripeCheckoutPostSavePlugin.
     * - Does NOT write payment status — status is set via webhook.
     *
     * @api
     */
    public function authorizePayment(OrderTransfer $orderTransfer): void;

    /**
     * Specification:
     * - Captures a previously authorized Stripe PaymentIntent.
     * - Called from StripeCaptureCommandPlugin.
     * - Does NOT write payment status — status is set via webhook.
     *
     * @api
     */
    public function capturePayment(OrderTransfer $orderTransfer): void;

    /**
     * Specification:
     * - Cancels (voids) a Stripe PaymentIntent.
     * - Called from StripeCancelCommandPlugin.
     * - Does NOT write payment status — status is set via webhook.
     *
     * @api
     */
    public function cancelPayment(OrderTransfer $orderTransfer): void;

    /**
     * Specification:
     * - Creates a Stripe Refund for the given order items.
     * - Refund amount is the sum of priceToPayAggregation for each item.
     * - Called from StripeRefundCommandPlugin.
     * - Does NOT write payment status — status is set via webhook.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems
     */
    public function refundPayment(OrderTransfer $orderTransfer, array $orderItems): void;

    /**
     * Specification:
     * - Filters the available payment methods to only include Stripe.
     * - Called from StripePaymentMethodFilterPlugin.
     *
     * @api
     */
    public function filterPaymentMethods(PaymentMethodsTransfer $paymentMethodsTransfer, QuoteTransfer $quoteTransfer): PaymentMethodsTransfer;

    /**
     * Specification:
     * - Generates a Stripe Connect onboarding URL for the given merchant (marketplace only).
     * - Creates a Stripe connected account if one does not exist yet.
     * - Saves the stripe_account_id to spy_stripe_merchant.
     *
     * @api
     */
    public function generateMerchantOnboardingUrl(string $merchantReference): string;
}
