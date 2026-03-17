<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;

class PaymentSaver
{
    public function __construct(
        protected StripeEntityManagerInterface $entityManager,
    ) {
    }

    public function savePayment(
        QuoteTransfer $quoteTransfer,
        SaveOrderTransfer $saveOrderTransfer,
        string $transactionId,
    ): void {
        $stripePaymentTransfer = (new StripePaymentTransfer())
            ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
            ->setFkSalesOrder($saveOrderTransfer->getIdSalesOrderOrFail())
            ->setAmount($quoteTransfer->getTotals() !== null ? $quoteTransfer->getTotals()->getGrandTotal() : null)
            ->setCurrencyCode($quoteTransfer->getCurrency() !== null ? $quoteTransfer->getCurrency()->getCode() : null)
            ->setTransactionId($transactionId);

        $this->entityManager->createPayment($stripePaymentTransfer);
    }
}
