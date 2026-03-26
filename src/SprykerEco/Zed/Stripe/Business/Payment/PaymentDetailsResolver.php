<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentDetailsResolver implements PaymentDetailsResolverInterface
{
    public function __construct(
        protected StripeIntentsInterface $stripeIntents,
        protected PaymentReaderInterface $paymentReader,
        protected StripeEntityManagerInterface $entityManager,
        protected StripeConfig $config,
        protected SharedStripeConfig $sharedConfig,
    ) {
    }

    public function resolve(string $orderReference): StripeIntentResponseTransfer
    {
        $payment = $this->paymentReader->getPaymentByOrderReference($orderReference);

        if ($payment === null) {
            return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
        }

        $transactionId = $payment->getTransactionId();

        if ($transactionId === null) {
            return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
        }

        $liveResponse = $this->stripeIntents->get(
            (new StripeIntentRequestTransfer())->setTransactionId($transactionId),
        );

        $idSalesOrder = $payment->getFkSalesOrder();

        // Stripe API unreachable — client_secret is not stored, cannot proceed
        if (!$liveResponse->getIsSuccessful()) {
            return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
        }

        $status = $liveResponse->getStatus();

        // Payment already captured or pending capture — redirect customer to success page
        if (in_array($status, $this->sharedConfig->getSuccessfulPaymentStatuses(), true)) {
            return (new StripeIntentResponseTransfer())
                ->setIsSuccessful(false)
                ->setStatus($status);
        }

        // PI was auto-canceled by Stripe (7-day expiry) — create a fresh PaymentIntent
        if ($status === 'canceled') {
            return $this->recreatePaymentIntent($payment, $orderReference, $idSalesOrder);
        }

        // PI is still open — reuse the existing client_secret
        if (in_array($status, $this->sharedConfig->getReusablePaymentStatuses(), true)) {
            return (new StripeIntentResponseTransfer())
                ->setIsSuccessful(true)
                ->setTransactionId($transactionId)
                ->setClientSecret($liveResponse->getClientSecret())
                ->setPublishableKey($this->config->getPublishableKey())
                ->setIdSalesOrder($idSalesOrder);
        }

        return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
    }

    protected function recreatePaymentIntent(StripePaymentTransfer $payment, string $orderReference, ?int $idSalesOrder): StripeIntentResponseTransfer
    {
        $quoteTransfer = (new QuoteTransfer())
            ->setOrderReference($orderReference)
            ->setTotals((new TotalsTransfer())->setGrandTotal($payment->getAmount()))
            ->setCurrency((new CurrencyTransfer())->setCode($payment->getCurrencyCode()));

        $newResponse = $this->stripeIntents->create(
            (new StripeIntentRequestTransfer())->setQuote($quoteTransfer),
        );

        if (!$newResponse->getIsSuccessful()) {
            return $newResponse;
        }

        $this->entityManager->updateTransactionId(
            $orderReference,
            $newResponse->getTransactionIdOrFail(),
        );

        return $newResponse
            ->setPublishableKey($this->config->getPublishableKey())
            ->setIdSalesOrder($idSalesOrder);
    }
}
