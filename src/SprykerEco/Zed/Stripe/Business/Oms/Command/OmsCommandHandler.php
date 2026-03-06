<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Oms\Command;

use Generated\Shared\Transfer\OrderTransfer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentAuthorizer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCanceller;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCapturer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentRefunder;

class OmsCommandHandler implements OmsCommandHandlerInterface
{
    public function __construct(
        protected PaymentAuthorizer $paymentAuthorizer,
        protected PaymentCapturer $paymentCapturer,
        protected PaymentCanceller $paymentCanceller,
        protected PaymentRefunder $paymentRefunder,
    ) {
    }

    public function authorize(OrderTransfer $orderTransfer): void
    {
        $this->paymentAuthorizer->authorizePayment($orderTransfer);
    }

    public function capture(OrderTransfer $orderTransfer, int $captureAmount = 0): void
    {
        $this->paymentCapturer->capturePayment($orderTransfer, $captureAmount);
    }

    public function cancel(OrderTransfer $orderTransfer): void
    {
        $this->paymentCanceller->cancelPayment($orderTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems
     */
    public function refund(OrderTransfer $orderTransfer, array $orderItems, int $refundAmount = 0): void
    {
        $this->paymentRefunder->refundPayment($orderTransfer, $orderItems, $refundAmount);
    }
}
