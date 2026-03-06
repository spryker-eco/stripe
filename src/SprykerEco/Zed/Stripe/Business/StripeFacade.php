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
use Spryker\Zed\Kernel\Business\AbstractFacade;

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
     */
    public function processWebhook(StripeWebhookPayloadTransfer $webhookPayloadTransfer): StripeWebhookProcessResponseTransfer
    {
        return $this->getFactory()
            ->createWebhookHandler()
            ->processWebhook($webhookPayloadTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function initializePayment(QuoteTransfer $quoteTransfer): StripeIntentResponseTransfer
    {
        return $this->getFactory()
            ->createPaymentInitializer()
            ->initializePayment($quoteTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPaymentDetails(string $orderReference): StripeIntentResponseTransfer
    {
        return $this->getFactory()
            ->createPaymentDetailsResolver()
            ->resolve($orderReference);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function savePayment(QuoteTransfer $quoteTransfer, SaveOrderTransfer $saveOrderTransfer, string $transactionId, string $clientSecret): void
    {
        $this->getFactory()
            ->createPaymentSaver()
            ->savePayment($quoteTransfer, $saveOrderTransfer, $transactionId, $clientSecret);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function authorizePayment(OrderTransfer $orderTransfer): void
    {
        $this->getFactory()
            ->createOmsCommandHandler()
            ->authorize($orderTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function capturePayment(OrderTransfer $orderTransfer, int $captureAmount = 0): void
    {
        $this->getFactory()
            ->createOmsCommandHandler()
            ->capture($orderTransfer, $captureAmount);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function cancelPayment(OrderTransfer $orderTransfer): void
    {
        $this->getFactory()
            ->createOmsCommandHandler()
            ->cancel($orderTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems
     */
    public function refundPayment(OrderTransfer $orderTransfer, array $orderItems, int $refundAmount = 0): void
    {
        $this->getFactory()
            ->createOmsCommandHandler()
            ->refund($orderTransfer, $orderItems, $refundAmount);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function generateMerchantOnboardingUrl(string $merchantReference, string $returnUrl, string $refreshUrl): string
    {
        return $this->getFactory()
            ->createMerchantOnboardingUrlGenerator()
            ->generateOnboardingUrl($merchantReference, $returnUrl, $refreshUrl);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function registerMerchantOnboarding(): void
    {
        $this->getFactory()
            ->createMerchantOnboardingRegistrar()
            ->register();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function transferFunds(OrderTransfer $orderTransfer, string $merchantReference, int $amount): void
    {
        $this->getFactory()
            ->createPaymentFundsTransfer()
            ->transfer($orderTransfer, $merchantReference, $amount);
    }
}
