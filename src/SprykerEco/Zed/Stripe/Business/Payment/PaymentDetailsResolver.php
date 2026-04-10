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
        $stripePaymentTransfer = $this->paymentReader->getPaymentByOrderReference($orderReference);

        if ($stripePaymentTransfer === null) {
            return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
        }

        $transactionId = $stripePaymentTransfer->getTransactionId();

        if ($transactionId === null) {
            return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
        }

        $stripeIntentResponseTransfer = $this->stripeIntents->get(
            (new StripeIntentRequestTransfer())->setTransactionId($transactionId),
        );

        $idSalesOrder = $stripePaymentTransfer->getFkSalesOrder();

        // Stripe API unreachable — client_secret is not stored, cannot proceed
        if (!$stripeIntentResponseTransfer->getIsSuccessful()) {
            return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
        }

        $status = $stripeIntentResponseTransfer->getStatus();

        // Payment already captured or pending capture — redirect customer to success page
        if (in_array($status, SharedStripeConfig::SUCCESSFUL_PAYMENT_STATUSES, true)) {
            return $stripeIntentResponseTransfer
                ->setIsSuccessful(false);
        }

        // PI canceled — either by the customer, OMS, or Stripe's 7-day expiry.
        if ($status === SharedStripeConfig::PAYMENT_STATUS_CANCELED) {
            return $stripeIntentResponseTransfer
                ->setIsSuccessful(false);
        }

        // PI is still open
        if (in_array($status, SharedStripeConfig::REUSABLE_PAYMENT_STATUSES, true)) {
            return $stripeIntentResponseTransfer
                ->setIsSuccessful(true)
                ->setCurrencyCode(strtoupper($stripeIntentResponseTransfer->getCurrencyCodeOrFail()))
                ->setPublishableKey($this->config->getPublishableKey())
                ->setIdSalesOrder($idSalesOrder);
        }

        return (new StripeIntentResponseTransfer())->setIsSuccessful(false);
    }
}
