<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntents;

class PaymentCanceller
{
    use LoggerTrait;

    public function __construct(
        protected StripeIntents $stripeIntents,
        protected PaymentReader $paymentReader,
    ) {
    }

    public function cancelPayment(OrderTransfer $orderTransfer): void
    {
        $stripePaymentTransfer = $this->paymentReader->getPaymentByOrderReference(
            $orderTransfer->getOrderReferenceOrFail(),
        );

        if ($stripePaymentTransfer === null || $stripePaymentTransfer->getTransactionId() === null) {
            $this->getLogger()->error('PaymentCanceller: no payment record or transactionId found', [
                'orderReference' => $orderTransfer->getOrderReference(),
            ]);

            return;
        }

        $stripeIntentRequestTransfer = (new StripeIntentRequestTransfer())
            ->setTransactionId($stripePaymentTransfer->getTransactionId());

        $stripeIntentResponseTransfer = $this->stripeIntents->cancel($stripeIntentRequestTransfer);

        if ($stripeIntentResponseTransfer->getIsSuccessful() !== true) {
            $this->getLogger()->error('PaymentCanceller: cancel failed', [
                'orderReference' => $orderTransfer->getOrderReference(),
                'transactionId' => $stripePaymentTransfer->getTransactionId(),
                'message' => $stripeIntentResponseTransfer->getMessage(),
            ]);
        }
    }
}
