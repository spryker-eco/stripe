<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntents;

class PaymentCapturer
{
    use LoggerTrait;

    public function __construct(
        protected StripeIntents $stripeIntents,
        protected PaymentReader $paymentReader,
        protected PaymentAppFacadeInterface $paymentAppFacade,
    ) {
    }

    public function capturePayment(OrderTransfer $orderTransfer, int $captureAmount = 0): void
    {
        $orderReference = $orderTransfer->getOrderReferenceOrFail();

        $stripePaymentTransfer = $this->paymentReader->getPaymentByOrderReference($orderReference);

        if ($stripePaymentTransfer === null || $stripePaymentTransfer->getTransactionId() === null) {
            $this->getLogger()->error('PaymentCapturer: no payment record or transactionId found', [
                'orderReference' => $orderReference,
            ]);

            return;
        }

        $stripeIntentCaptureRequestTransfer = (new StripeIntentCaptureRequestTransfer())
            ->setTransactionId($stripePaymentTransfer->getTransactionId())
            ->setAmount($captureAmount ?: null);

        $stripeIntentCaptureResponseTransfer = $this->stripeIntents->capture($stripeIntentCaptureRequestTransfer);

        if ($stripeIntentCaptureResponseTransfer->getIsSuccessful() !== true) {
            $this->getLogger()->error('PaymentCapturer: capture failed', [
                'orderReference' => $orderReference,
                'transactionId' => $stripePaymentTransfer->getTransactionId(),
                'message' => $stripeIntentCaptureResponseTransfer->getMessage(),
            ]);

            return;
        }

        // When the PaymentIntent was already captured manually on the Stripe Dashboard, its status
        // is `succeeded` and StripeIntents::capture() returns PAYMENT_STATUS_CAPTURED immediately.
        // In this case no webhook will fire, so we update the OMS status directly as a fallback.
        if ($stripeIntentCaptureResponseTransfer->getStatus() === SharedStripeConfig::PAYMENT_STATUS_CAPTURED) {
            $this->paymentAppFacade->savePaymentAppPaymentStatus(
                (new PaymentCapturedTransfer())->setOrderReference($orderReference),
            );
        }
    }

}
