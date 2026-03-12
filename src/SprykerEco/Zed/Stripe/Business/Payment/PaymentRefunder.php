<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeRefundRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeRefundsInterface;

class PaymentRefunder implements PaymentRefunderInterface
{
    use LoggerTrait;

    public function __construct(
        protected StripeRefundsInterface $stripeRefunds,
        protected PaymentReaderInterface $paymentReader,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function refundPayment(OrderTransfer $orderTransfer, array $orderItems, int $refundAmount = 0): void
    {
        $stripePaymentTransfer = $this->paymentReader->getPaymentByOrderReference(
            $orderTransfer->getOrderReferenceOrFail(),
        );

        if ($stripePaymentTransfer === null || $stripePaymentTransfer->getTransactionId() === null) {
            $this->getLogger()->error('PaymentRefunder: no payment record or transactionId found', [
                'orderReference' => $orderTransfer->getOrderReference(),
            ]);

            return;
        }

        $orderItemSkus = array_map(
            static fn (ItemTransfer $item): string => $item->getSkuOrFail(),
            $orderItems,
        );

        $stripeRefundRequestTransfer = (new StripeRefundRequestTransfer())
            ->setTransactionId($stripePaymentTransfer->getTransactionId())
            ->setAmount($refundAmount)
            ->setOrderItemSkus($orderItemSkus);

        $stripeRefundResponseTransfer = $this->stripeRefunds->create($stripeRefundRequestTransfer);

        if ($stripeRefundResponseTransfer->getIsSuccessful() !== true) {
            $this->getLogger()->error('PaymentRefunder: refund failed', [
                'orderReference' => $orderTransfer->getOrderReference(),
                'transactionId' => $stripePaymentTransfer->getTransactionId(),
                'message' => $stripeRefundResponseTransfer->getMessage(),
            ]);
        }
    }
}
