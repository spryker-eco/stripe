<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentSaver
{
    public function __construct(
        protected StripeEntityManagerInterface $entityManager,
        protected StripeConfig $config,
    ) {
    }

    public function savePayment(QuoteTransfer $quoteTransfer, SaveOrderTransfer $saveOrderTransfer): void
    {
        $stripePaymentTransfer = (new StripePaymentTransfer())
            ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
            ->setFkSalesOrder($saveOrderTransfer->getIdSalesOrderOrFail())
            ->setAmount($quoteTransfer->getTotals() !== null ? $quoteTransfer->getTotals()->getGrandTotal() : null)
            ->setCurrencyCode($quoteTransfer->getCurrency() !== null ? $quoteTransfer->getCurrency()->getCode() : null)
            ->setBusinessModel($this->config->getBusinessModel())
            ->setTransactionId($this->resolveTransactionId($quoteTransfer));

        $this->entityManager->createPayment($stripePaymentTransfer);
    }

    protected function resolveTransactionId(QuoteTransfer $quoteTransfer): ?string
    {
        foreach ($quoteTransfer->getPayments() as $paymentTransfer) {
            if (
                $paymentTransfer->getPaymentProvider() === SharedStripeConfig::PAYMENT_PROVIDER_NAME
                && $paymentTransfer->getStripe() !== null
                && $paymentTransfer->getStripe()->getTransactionId() !== null
            ) {
                return $paymentTransfer->getStripe()->getTransactionId();
            }
        }

        /** @var \Generated\Shared\Transfer\PaymentTransfer|null $payment */
        $payment = $quoteTransfer->getPayment();
        if ($payment instanceof PaymentTransfer && $payment->getStripe() !== null && $payment->getStripe()->getTransactionId() !== null) {
            return $payment->getStripe()->getTransactionId();
        }

        return null;
    }
}
