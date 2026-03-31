<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;

class PaymentAuthorizer implements PaymentAuthorizerInterface
{
    use LoggerTrait;

    public function __construct(
        protected StripeIntentsInterface $stripeIntents,
        protected PaymentReaderInterface $paymentReader,
    ) {
    }

    public function authorizePayment(OrderTransfer $orderTransfer): void
    {
        $stripePaymentTransfer = $this->paymentReader->getPaymentByOrderReference(
            $orderTransfer->getOrderReferenceOrFail(),
        );

        if ($stripePaymentTransfer === null || $stripePaymentTransfer->getTransactionId() === null) {
            $this->getLogger()->warning('PaymentAuthorizer: no payment record or transactionId found', [
                'orderReference' => $orderTransfer->getOrderReference(),
            ]);

            return;
        }

        $stripeIntentRequestTransfer = (new StripeIntentRequestTransfer())
            ->setTransactionId($stripePaymentTransfer->getTransactionId());

        $stripeIntentResponseTransfer = $this->stripeIntents->get($stripeIntentRequestTransfer);

        if ($stripeIntentResponseTransfer->getIsSuccessful() !== true) {
            $this->getLogger()->error('PaymentAuthorizer: failed to retrieve payment intent', [
                'orderReference' => $orderTransfer->getOrderReference(),
                'transactionId' => $stripePaymentTransfer->getTransactionId(),
                'message' => $stripeIntentResponseTransfer->getMessage(),
            ]);
        }
    }
}
