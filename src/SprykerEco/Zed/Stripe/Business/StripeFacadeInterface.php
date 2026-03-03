<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\PaymentMethodsTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Generated\Shared\Transfer\StripeWebhookPayloadTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;

interface StripeFacadeInterface
{
    /**
     * Specification:
     * - Saves payment data during checkout order save phase.
     * - Creates records in spy_stripe and spy_stripe_order_item tables.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\SaveOrderTransfer $saveOrderTransfer
     *
     * @return void
     */
    public function saveOrderPayment(QuoteTransfer $quoteTransfer, SaveOrderTransfer $saveOrderTransfer): void;

    /**
     * Specification:
     * - Executes post-save checkout hook.
     * - Typically triggers payment authorization for the order.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\CheckoutResponseTransfer $checkoutResponseTransfer
     *
     * @return void
     */
    public function executePostSaveHook(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void;

    /**
     * Specification:
     * - Filters available payment methods based on quote data and business rules.
     *  - Communicates with payment provider via PaymentMethodAdapter.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\PaymentMethodsTransfer $paymentMethodsTransfer
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\PaymentMethodsTransfer
     */
    public function filterPaymentMethods(
        PaymentMethodsTransfer $paymentMethodsTransfer,
        QuoteTransfer $quoteTransfer,
    ): PaymentMethodsTransfer;

    /**
     * Specification:
     * - Executes payment authorization command.
     * - Called by OMS AuthorizePlugin during order processing.
     * - Communicates with payment provider via AuthorizeAdapter.
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeAuthorizeCommand(SpySalesOrder $orderEntity, array $orderItems): void;

    /**
     * Specification:
     * - Executes payment capture command.
     * - Called by OMS CapturePlugin to capture authorized funds.
     * - Communicates with payment provider via CaptureAdapter.
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeCaptureCommand(SpySalesOrder $orderEntity, array $orderItems): void;

    /**
     * Specification:
     * - Executes payment cancellation command.
     * - Called by OMS CancelPlugin to cancel authorized but not captured payment.
     * - Communicates with payment provider via CancelAdapter.
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeCancelCommand(SpySalesOrder $orderEntity, array $orderItems): void;

    /**
     * Specification:
     * - Checks if payment is authorized.
     * - Called by OMS IsAuthorizedPlugin condition.
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return bool
     */
    public function isPaymentAuthorized(SpySalesOrderItem $orderItemEntity): bool;

    /**
     * Specification:
     * - Checks if payment authorization failed.
     * - Called by OMS IsAuthorizationFailedPlugin condition.
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return bool
     */
    public function isPaymentAuthorizationFailed(SpySalesOrderItem $orderItemEntity): bool;

    /**
     * Specification:
     * - Checks if payment is captured.
     * - Called by OMS IsCapturedPlugin condition.
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return bool
     */
    public function isPaymentCaptured(SpySalesOrderItem $orderItemEntity): bool;

    /**
     * Specification:
     * - Processes incoming webhook notification from payment service provider.
     * - Saves webhook payload to database for audit trail.
     * - Updates payment status based on webhook data.
     * - Optionally triggers OMS state machine transitions.
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
