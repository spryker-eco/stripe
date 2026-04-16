<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Payment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeIntentCaptureResponseTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Spryker\Zed\PaymentApp\Business\PaymentAppFacadeInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCapturer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Payment
 * @group PaymentCapturerTest
 */
class PaymentCapturerTest extends Unit
{
    protected const ORDER_REFERENCE = 'DE--001';

    protected const TRANSACTION_ID = 'pi_test_123';

    public function testCaptureSkipsWhenNoPaymentRecordFound(): void
    {
        // Arrange
        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->expects($this->never())->method('capture');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')->willReturn(null);

        $capturer = $this->createCapturer($stripeIntentsMock, $paymentReaderMock);

        // Act
        $capturer->capturePayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCaptureSkipsWhenTransactionIdIsNull(): void
    {
        // Arrange
        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->expects($this->never())->method('capture');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')
            ->willReturn((new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE));

        $capturer = $this->createCapturer($stripeIntentsMock, $paymentReaderMock);

        // Act
        $capturer->capturePayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCaptureDoesNotSaveStatusWhenStripeReturnsFailure(): void
    {
        // Arrange
        $failedResponse = (new StripeIntentCaptureResponseTransfer())
            ->setIsSuccessful(false)
            ->setMessage('Capture failed on Stripe side');

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('capture')->willReturn($failedResponse);

        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->never())->method('savePaymentAppPaymentStatus');

        $capturer = $this->createCapturer($stripeIntentsMock, $this->createPaymentReaderMock(), $paymentAppFacadeMock);

        // Act
        $capturer->capturePayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCaptureDoesNotSaveStatusWhenStripeReturnsNonCapturedStatus(): void
    {
        // Arrange — Stripe accepted the capture but payment is still processing (status ≠ captured)
        $processingResponse = (new StripeIntentCaptureResponseTransfer())
            ->setIsSuccessful(true)
            ->setStatus(SharedStripeConfig::PAYMENT_STATUS_CAPTURE_REQUESTED);

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('capture')->willReturn($processingResponse);

        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->never())->method('savePaymentAppPaymentStatus');

        $capturer = $this->createCapturer($stripeIntentsMock, $this->createPaymentReaderMock(), $paymentAppFacadeMock);

        // Act
        $capturer->capturePayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCaptureSavesStatusImmediatelyWhenAlreadyCapturedOnStripeDashboard(): void
    {
        // Arrange — PaymentIntent was already captured manually on Stripe Dashboard, status = captured immediately.
        // No webhook fires in this case, so the capturer writes status directly as a fallback.
        $alreadyCapturedResponse = (new StripeIntentCaptureResponseTransfer())
            ->setIsSuccessful(true)
            ->setStatus(SharedStripeConfig::PAYMENT_STATUS_CAPTURED);

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('capture')->willReturn($alreadyCapturedResponse);

        $paymentAppFacadeMock = $this->createMock(PaymentAppFacadeInterface::class);
        $paymentAppFacadeMock->expects($this->once())->method('savePaymentAppPaymentStatus');

        $capturer = $this->createCapturer($stripeIntentsMock, $this->createPaymentReaderMock(), $paymentAppFacadeMock);

        // Act
        $capturer->capturePayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE));
    }

    public function testCapturePassesExplicitAmountToStripeRequest(): void
    {
        // Arrange
        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->expects($this->once())
            ->method('capture')
            ->with($this->callback(
                fn ($request): bool => $request->getAmount() === 9900,
            ))
            ->willReturn((new StripeIntentCaptureResponseTransfer())->setIsSuccessful(false));

        $capturer = $this->createCapturer($stripeIntentsMock, $this->createPaymentReaderMock());

        // Act
        $capturer->capturePayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE), 9900);
    }

    public function testCapturePassesNullAmountToStripeRequestWhenZeroPassed(): void
    {
        // Arrange — amount=0 means "capture full amount", which maps to null in the Stripe request
        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->expects($this->once())
            ->method('capture')
            ->with($this->callback(
                fn ($request): bool => $request->getAmount() === null,
            ))
            ->willReturn((new StripeIntentCaptureResponseTransfer())->setIsSuccessful(false));

        $capturer = $this->createCapturer($stripeIntentsMock, $this->createPaymentReaderMock());

        // Act
        $capturer->capturePayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE), 0);
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

    protected function createCapturer(
        StripeIntentsInterface $stripeIntents,
        PaymentReaderInterface $paymentReader,
        ?PaymentAppFacadeInterface $paymentAppFacade = null,
    ): PaymentCapturer {
        return new PaymentCapturer(
            $stripeIntents,
            $paymentReader,
            $paymentAppFacade ?? $this->createMock(PaymentAppFacadeInterface::class),
        );
    }
}
