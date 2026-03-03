<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Client\Stripe;

use Generated\Shared\Transfer\StripeAuthorizeRequestTransfer;
use Generated\Shared\Transfer\StripeAuthorizeResponseTransfer;
use Generated\Shared\Transfer\StripeCancelRequestTransfer;
use Generated\Shared\Transfer\StripeCancelResponseTransfer;
use Generated\Shared\Transfer\StripeCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeCaptureResponseTransfer;
use Generated\Shared\Transfer\StripePaymentMethodsRequestTransfer;
use Generated\Shared\Transfer\StripePaymentMethodsResponseTransfer;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;

interface StripeClientInterface
{
    /**
     * Specification:
     * - Sends authorization request to payment provider.
     * - Maps request data to provider-specific format.
     * - Returns authorization response.
     * - Logs the request, response and error.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\StripeAuthorizeRequestTransfer $stripeAuthorizeRequestTransfer
     *
     * @return \Generated\Shared\Transfer\StripeAuthorizeResponseTransfer
     */
    public function authorize(StripeAuthorizeRequestTransfer $stripeAuthorizeRequestTransfer): StripeAuthorizeResponseTransfer;

    /**
     * Specification:
     * - Sends capture request to payment provider to capture authorized amount.
     * - Maps request data to provider-specific format.
     * - Returns capture response.
     * - Logs the request, response and error.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\StripeCaptureRequestTransfer $stripeAuthorizeResponseTransfer
     *
     * @return \Generated\Shared\Transfer\StripeCaptureResponseTransfer
     */
    public function capture(StripeCaptureRequestTransfer $stripeAuthorizeResponseTransfer): StripeCaptureResponseTransfer;

    /**
     * Specification:
     * - Sends cancel request to payment provider to cancel authorized payment.
     * - Maps request data to provider-specific format.
     * - Returns cancellation response.
     * - Logs the request, response and error.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\StripeCancelRequestTransfer $stripeCancelRequestTransfer
     *
     * @return \Generated\Shared\Transfer\StripeCancelResponseTransfer
     */
    public function cancel(StripeCancelRequestTransfer $stripeCancelRequestTransfer): StripeCancelResponseTransfer;

    /**
     * Specification:
     * - Retrieves available payment methods from payment provider.
     * - Maps request data to provider-specific format.
     * - Returns list of available payment methods for current context.
     * - Logs the request, response and error.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\StripePaymentMethodsRequestTransfer $stripePaymentMethodsRequestTransfer
     *
     * @return \Generated\Shared\Transfer\StripePaymentMethodsResponseTransfer
     */
    public function getPaymentMethods(
        StripePaymentMethodsRequestTransfer $stripePaymentMethodsRequestTransfer,
    ): StripePaymentMethodsResponseTransfer;

    /**
     * Specification:
     * - Sends webhook payload to Zed for processing.
     * - Zed will save webhook to database and update payment status.
     * - Returns webhook processing response.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\StripeWebhookPayloadTransfer $webhookPayloadTransfer
     *
     * @return \Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer
     */
    public function processWebhook(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer,
    ): StripeWebhookProcessResponseTransfer;
}
