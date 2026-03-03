<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntents;

class PaymentCapturer
{
    use LoggerTrait;

    public function __construct(
        protected StripeIntents $stripeIntents,
        protected PaymentReader $paymentReader,
    ) {
    }

    public function capturePayment(OrderTransfer $orderTransfer): void
    {
        $stripePaymentTransfer = $this->paymentReader->getPaymentByOrderReference(
            $orderTransfer->getOrderReferenceOrFail(),
        );

        if ($stripePaymentTransfer === null || $stripePaymentTransfer->getTransactionId() === null) {
            $this->getLogger()->error('PaymentCapturer: no payment record or transactionId found', [
                'orderReference' => $orderTransfer->getOrderReference(),
            ]);

            return;
        }

        $stripeIntentCaptureRequestTransfer = (new StripeIntentCaptureRequestTransfer())
            ->setTransactionId($stripePaymentTransfer->getTransactionId());

        $stripeIntentCaptureResponseTransfer = $this->stripeIntents->capture($stripeIntentCaptureRequestTransfer);

        if ($stripeIntentCaptureResponseTransfer->getIsSuccessful() !== true) {
            $this->getLogger()->error('PaymentCapturer: capture failed', [
                'orderReference' => $orderTransfer->getOrderReference(),
                'transactionId' => $stripePaymentTransfer->getTransactionId(),
                'message' => $stripeIntentCaptureResponseTransfer->getMessage(),
            ]);
        }
    }
}
