<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCancellationFailedTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use Spryker\Zed\SalesPaymentDetail\Business\SalesPaymentDetailFacadeInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntents;

class PaymentCanceller
{
    use LoggerTrait;

    public function __construct(
        protected StripeIntents $stripeIntents,
        protected PaymentReader $paymentReader,
        protected PaymentAppFacadeInterface $paymentAppFacade,
        protected SalesPaymentDetailFacadeInterface $salesPaymentDetailFacade,
    ) {
    }

    public function cancelPayment(OrderTransfer $orderTransfer): void
    {
        $orderReference = $orderTransfer->getOrderReferenceOrFail();

        $stripePaymentTransfer = $this->paymentReader->getPaymentByOrderReference($orderReference);

        if ($stripePaymentTransfer === null || $stripePaymentTransfer->getTransactionId() === null) {
            $this->getLogger()->warning('PaymentCanceller: no payment record or transactionId found', [
                'orderReference' => $orderReference,
            ]);

            return;
        }

        $transactionId = $stripePaymentTransfer->getTransactionId();

        $stripeIntentRequestTransfer = (new StripeIntentRequestTransfer())
            ->setTransactionId($transactionId);

        $stripeIntentResponseTransfer = $this->stripeIntents->cancel($stripeIntentRequestTransfer);

        if ($stripeIntentResponseTransfer->getIsSuccessful() !== true) {
            $this->paymentAppFacade->savePaymentAppPaymentStatus(
                (new PaymentCancellationFailedTransfer())->setOrderReference($orderReference),
            );

            return;
        }

        $this->paymentAppFacade->savePaymentAppPaymentStatus(
            (new PaymentCanceledTransfer())->setOrderReference($orderReference),
        );

        $this->salesPaymentDetailFacade->handlePaymentUpdated(
            (new PaymentUpdatedTransfer())
                ->setEntityReference($orderReference)
                ->setPaymentReference($transactionId)
                ->setDetails((string)json_encode([
                    'id' => $transactionId,
                    'status' => $stripeIntentResponseTransfer->getStatus(),
                ])),
        );
    }
}
