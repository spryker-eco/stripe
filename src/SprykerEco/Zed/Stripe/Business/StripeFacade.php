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
use SprykerEco\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeBusinessFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface getEntityManager()
 */
class StripeFacade extends AbstractFacade implements StripeFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\SaveOrderTransfer $saveOrderTransfer
     *
     * @return void
     */
    public function saveOrderPayment(QuoteTransfer $quoteTransfer, SaveOrderTransfer $saveOrderTransfer): void
    {
        $this->getFactory()
            ->createPaymentSaver()
            ->saveOrderPayment($quoteTransfer, $saveOrderTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\CheckoutResponseTransfer $checkoutResponseTransfer
     *
     * @return void
     */
    public function executePostSaveHook(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        $this->getFactory()
            ->createPaymentAuthorizer()
            ->executePostSaveHook($quoteTransfer, $checkoutResponseTransfer);
    }

    /**
     * {@inheritDoc}
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
    ): PaymentMethodsTransfer {
        return $this->getFactory()
            ->createPaymentMethodFilter()
            ->filterPaymentMethods($paymentMethodsTransfer, $quoteTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeAuthorizeCommand(SpySalesOrder $orderEntity, array $orderItems): void
    {
        $this->getFactory()
            ->createOmsCommandHandler()
            ->executeAuthorizeCommand($orderEntity, $orderItems);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeCaptureCommand(SpySalesOrder $orderEntity, array $orderItems): void
    {
        $this->getFactory()
            ->createOmsCommandHandler()
            ->executeCaptureCommand($orderEntity, $orderItems);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeCancelCommand(SpySalesOrder $orderEntity, array $orderItems): void
    {
        $this->getFactory()
            ->createOmsCommandHandler()
            ->executeCancelCommand($orderEntity, $orderItems);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return bool
     */
    public function isPaymentAuthorized(SpySalesOrderItem $orderItemEntity): bool
    {
        return $this->getFactory()
            ->createOmsConditionChecker()
            ->isPaymentAuthorized($orderItemEntity);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return bool
     */
    public function isPaymentAuthorizationFailed(SpySalesOrderItem $orderItemEntity): bool
    {
        return $this->getFactory()
            ->createOmsConditionChecker()
            ->isPaymentAuthorizationFailed($orderItemEntity);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return bool
     */
    public function isPaymentCaptured(SpySalesOrderItem $orderItemEntity): bool
    {
        return $this->getFactory()
            ->createOmsConditionChecker()
            ->isPaymentCaptured($orderItemEntity);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\StripeWebhookPayloadTransfer $webhookPayloadTransfer
     *
     * @return \Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer
     */
    public function processWebhook(
        StripeWebhookPayloadTransfer $webhookPayloadTransfer
    ): StripeWebhookProcessResponseTransfer {
        return $this->getFactory()
            ->createNotificationProcessor()
            ->processWebhook($webhookPayloadTransfer);
    }
}
