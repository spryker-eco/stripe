<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Payment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCancellationFailedTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use Spryker\Zed\SalesPaymentDetail\Business\SalesPaymentDetailFacadeInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCanceller;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Payment
 * @group PaymentCancellerTest
 */
class PaymentCancellerTest extends Unit
{
    protected const ORDER_REFERENCE = 'DE--001';

    protected const TRANSACTION_ID = 'pi_test_123';

    public function testCancelSkipsWhenNoPaymentRecordFound(): void
    {
        // Arrange
        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->expects($this->never())->method('cancel');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')->willReturn(null);

        $canceller = $this->createCanceller($stripeIntentsMock, $paymentReaderMock);

        // Act
        $canceller->cancelPayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCancelSkipsWhenTransactionIdIsNull(): void
    {
        // Arrange
        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->expects($this->never())->method('cancel');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')
            ->willReturn((new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE));

        $canceller = $this->createCanceller($stripeIntentsMock, $paymentReaderMock);

        // Act
        $canceller->cancelPayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCancelSavesCancellationFailedStatusWhenStripeReturnsFailure(): void
    {
        // Arrange
        $failedResponse = (new StripeIntentResponseTransfer())
            ->setIsSuccessful(false)
            ->setMessage('PaymentIntent cannot be canceled in its current state.');

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('cancel')->willReturn($failedResponse);

        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentCancellationFailedTransfer::class));

        $salesPaymentDetailFacadeMock = $this->createMock(SalesPaymentDetailFacadeInterface::class);
        $salesPaymentDetailFacadeMock->expects($this->never())->method('handlePaymentUpdated');

        $canceller = $this->createCanceller(
            $stripeIntentsMock,
            $this->createPaymentReaderMock(),
            $paymentAppFacadeMock,
            $salesPaymentDetailFacadeMock,
        );

        // Act
        $canceller->cancelPayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCancelSavesCanceledStatusWhenStripeSucceeds(): void
    {
        // Arrange
        $successResponse = (new StripeIntentResponseTransfer())
            ->setIsSuccessful(true)
            ->setStatus('canceled');

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('cancel')->willReturn($successResponse);

        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->once())
            ->method('savePaymentAppPaymentStatus')
            ->with($this->isInstanceOf(PaymentCanceledTransfer::class));

        $canceller = $this->createCanceller($stripeIntentsMock, $this->createPaymentReaderMock(), $paymentAppFacadeMock);

        // Act
        $canceller->cancelPayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCancelCallsHandlePaymentUpdatedWithTransactionIdAfterSuccessfulCancel(): void
    {
        // Arrange
        $successResponse = (new StripeIntentResponseTransfer())
            ->setIsSuccessful(true)
            ->setStatus('canceled');

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('cancel')->willReturn($successResponse);

        $salesPaymentDetailFacadeMock = $this->createMock(SalesPaymentDetailFacadeInterface::class);
        $salesPaymentDetailFacadeMock->expects($this->once())
            ->method('handlePaymentUpdated')
            ->with($this->callback(function ($transfer): bool {
                return $transfer->getEntityReference() === static::ORDER_REFERENCE
                    && $transfer->getPaymentReference() === static::TRANSACTION_ID;
            }));

        $canceller = $this->createCanceller(
            $stripeIntentsMock,
            $this->createPaymentReaderMock(),
            salesPaymentDetailFacade: $salesPaymentDetailFacadeMock,
        );

        // Act
        $canceller->cancelPayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    protected function createPaymentReaderMock(): PaymentReaderInterface
    {
        $mock = $this->createMock(PaymentReaderInterface::class);
        $mock->method('getPaymentByOrderReference')->willReturn(
            (new StripePaymentTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->setTransactionId(static::TRANSACTION_ID),
        );

        return $mock;
    }

    protected function createCanceller(
        StripeIntentsInterface $stripeIntents,
        PaymentReaderInterface $paymentReader,
        ?PaymentAppFacadeInterface $paymentAppFacade = null,
        ?SalesPaymentDetailFacadeInterface $salesPaymentDetailFacade = null,
    ): PaymentCanceller {
        return new PaymentCanceller(
            $stripeIntents,
            $paymentReader,
            $paymentAppFacade ?? $this->createMock(PaymentAppFacadeInterface::class),
            $salesPaymentDetailFacade ?? $this->createMock(SalesPaymentDetailFacadeInterface::class),
        );
    }
}
