<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Payment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Generated\Shared\Transfer\StripeRefundRequestTransfer;
use Generated\Shared\Transfer\StripeRefundResponseTransfer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentRefunder;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeRefundsInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Payment
 * @group PaymentRefunderTest
 */
class PaymentRefunderTest extends Unit
{
    protected const ORDER_REFERENCE = 'DE--001';

    protected const TRANSACTION_ID = 'pi_test_123';

    public function testRefundSkipsWhenNoPaymentRecordFound(): void
    {
        // Arrange
        $stripeRefundsMock = $this->createMock(StripeRefundsInterface::class);
        $stripeRefundsMock->expects($this->never())->method('create');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')->willReturn(null);

        $refunder = new PaymentRefunder($stripeRefundsMock, $paymentReaderMock);

        // Act
        $refunder->refundPayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE), []);
    }

    public function testRefundSkipsWhenTransactionIdIsNull(): void
    {
        // Arrange
        $stripeRefundsMock = $this->createMock(StripeRefundsInterface::class);
        $stripeRefundsMock->expects($this->never())->method('create');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')
            ->willReturn((new StripePaymentTransfer())->setOrderReference(static::ORDER_REFERENCE));

        $refunder = $this->createRefunder($stripeRefundsMock, $paymentReaderMock);

        // Act
        $refunder->refundPayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE), []);
    }

    public function testRefundCallsStripeWithCorrectTransactionIdAndAmount(): void
    {
        // Arrange
        $capturedRequest = null;

        $stripeRefundsMock = $this->createMock(StripeRefundsInterface::class);
        $stripeRefundsMock->method('create')->willReturnCallback(
            function (StripeRefundRequestTransfer $request) use (&$capturedRequest): StripeRefundResponseTransfer {
                $capturedRequest = $request;

                return (new StripeRefundResponseTransfer())->setIsSuccessful(true);
            },
        );

        $refunder = $this->createRefunder($stripeRefundsMock, $this->createPaymentReaderMock());

        // Act
        $refunder->refundPayment(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            [],
            5000,
        );

        // Assert
        $this->assertNotNull($capturedRequest);
        $this->assertSame(static::TRANSACTION_ID, $capturedRequest->getTransactionId());
        $this->assertSame(5000, $capturedRequest->getAmount());
    }

    public function testRefundMapsOrderItemsToSkusInRequest(): void
    {
        // Arrange
        $orderItems = [
            (new ItemTransfer())->setSku('SKU-001'),
            (new ItemTransfer())->setSku('SKU-002'),
        ];

        $capturedRequest = null;

        $stripeRefundsMock = $this->createMock(StripeRefundsInterface::class);
        $stripeRefundsMock->method('create')->willReturnCallback(
            function (StripeRefundRequestTransfer $request) use (&$capturedRequest): StripeRefundResponseTransfer {
                $capturedRequest = $request;

                return (new StripeRefundResponseTransfer())->setIsSuccessful(true);
            },
        );

        $refunder = $this->createRefunder($stripeRefundsMock, $this->createPaymentReaderMock());

        // Act
        $refunder->refundPayment(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            $orderItems,
        );

        // Assert
        $this->assertNotNull($capturedRequest);
        $this->assertSame(
            array_map(static fn (ItemTransfer $item): string => $item->getSkuOrFail(), $orderItems),
            $capturedRequest->getOrderItemSkus(),
        );
    }

    public function testRefundWithZeroAmountPassesZeroToStripe(): void
    {
        // Arrange
        $capturedRequest = null;

        $stripeRefundsMock = $this->createMock(StripeRefundsInterface::class);
        $stripeRefundsMock->method('create')->willReturnCallback(
            function (StripeRefundRequestTransfer $request) use (&$capturedRequest): StripeRefundResponseTransfer {
                $capturedRequest = $request;

                return (new StripeRefundResponseTransfer())->setIsSuccessful(true);
            },
        );

        $refunder = $this->createRefunder($stripeRefundsMock, $this->createPaymentReaderMock());

        // Act
        $refunder->refundPayment((new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE), []);

        // Assert
        $this->assertNotNull($capturedRequest);
        $this->assertSame(0, $capturedRequest->getAmount());
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

    protected function createRefunder(
        StripeRefundsInterface $stripeRefunds,
        PaymentReaderInterface $paymentReader,
    ): PaymentRefunder {
        return new PaymentRefunder($stripeRefunds, $paymentReader);
    }
}
