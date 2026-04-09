<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentDetailsResolver implements PaymentDetailsResolverInterface
{
    public function __construct(
        protected StripeIntentsInterface $stripeIntents,
        protected PaymentReaderInterface $paymentReader,
        protected StripeConfig $config,
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
        if (in_array($status, SharedStripeConfig::SUCCESSFUL_PAYMENT_STATUSES, true)) {
            return (new StripeIntentResponseTransfer())
                ->setIsSuccessful(false)
                ->setStatus($status);
        }

        // PI canceled — either by the customer, OMS, or Stripe's 7-day expiry.
        if ($status === SharedStripeConfig::PAYMENT_STATUS_CANCELED) {
            return (new StripeIntentResponseTransfer())
                ->setIsSuccessful(false)
                ->setStatus(SharedStripeConfig::PAYMENT_STATUS_CANCELED);
        }

        // PI is still open — reuse the existing client_secret
        if (in_array($status, SharedStripeConfig::REUSABLE_PAYMENT_STATUSES, true)) {
            return (new StripeIntentResponseTransfer())
                ->setIsSuccessful(true)
                ->setTransactionId($transactionId)
                ->setClientSecret($liveResponse->getClientSecret())
                ->setPublishableKey($this->config->getPublishableKey())
                ->setIdSalesOrder($idSalesOrder);
        }

        return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
    }
}
