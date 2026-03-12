<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business;

use Generated\Shared\Transfer\OrderTransfer;
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
     * - Reads clientSecret and transactionId from spy_stripe_payment by orderReference.
     * - Adds publishableKey from config.
     * - Used by the Yves Stripe payment page to mount Stripe Elements.
     *
     * @api
     */
    public function getPaymentDetails(string $orderReference): StripeIntentResponseTransfer;

    /**
     * Specification:
     * - Persists a `spy_stripe_payment` record linking the order to the Stripe PaymentIntent.
     * - Called from StripeCheckoutPostSavePlugin after PaymentIntent creation.
     *
     * @api
     */
    public function savePayment(QuoteTransfer $quoteTransfer, SaveOrderTransfer $saveOrderTransfer, string $transactionId, string $clientSecret): void;

    /**
     * Specification:
     * - Verifies the Stripe PaymentIntent is in an authorized (requires_capture) state.
     * - Called from StripeAuthorizeCommandPlugin (OMS command).
     * - Does NOT write payment status — status is set via payment_intent.amount_capturable_updated webhook.
     *
     * @api
     */
    public function authorizePayment(OrderTransfer $orderTransfer): void;

    /**
     * Specification:
     * - Captures a previously authorized Stripe PaymentIntent.
     * - When `$captureAmount` is non-zero, performs a partial capture for that amount.
     * - Called from StripeCaptureCommandPlugin.
     * - Does NOT write payment status — status is set via webhook.
     *
     * @api
     */
    public function capturePayment(OrderTransfer $orderTransfer, int $captureAmount = 0): void;

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
     * - Uses `$refundAmount` when provided; otherwise sums `refundableAmount` across `$orderItems`.
     * - Called from StripeRefundCommandPlugin.
     * - Does NOT write payment status — status is set via webhook.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems
     */
    public function refundPayment(OrderTransfer $orderTransfer, array $orderItems, int $refundAmount = 0): void;

    /**
     * Specification:
     * - Generates a Stripe Connect onboarding URL for the given merchant (marketplace only).
     * - Creates a Stripe connected account if one does not exist yet.
     * - Saves the stripe_account_id to spy_stripe_merchant.
     * - Uses returnUrl and refreshUrl as the Stripe account link redirect targets.
     *
     * @api
     */
    public function generateMerchantOnboardingUrl(string $merchantReference, string $returnUrl, string $refreshUrl): string;

    /**
     * Specification:
     * - Registers Stripe as ready to support merchant onboarding via MerchantAppFacade.
     * - Stores onboarding strategy ('redirect') and initialize URL in MerchantApp module.
     * - Called from StripeMarketplaceInstallerPlugin during setup:init-db.
     *
     * @api
     */
    public function registerMerchantOnboarding(): void;

    /**
     * Specification:
     * - Transfers funds to the merchant's Stripe connected account (marketplace only).
     * - Reads the payment's latest charge ID and the merchant's Stripe account ID.
     * - Called from StripeTransferCommandPlugin via OMS command.
     *
     * @api
     */
    public function transferFunds(OrderTransfer $orderTransfer, string $merchantReference, int $amount): void;
}
