<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Oms;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use SprykerEco\Zed\Stripe\Business\Oms\Command\OmsCommandHandler;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentAuthorizerInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCancellerInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentCapturerInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentRefunderInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Oms
 * @group OmsCommandHandlerTest
 */
class OmsCommandHandlerTest extends Unit
{
    protected const ORDER_REFERENCE = 'DE--001';

    public function testAuthorizeDelegatesToAuthorizerWithCorrectOrder(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE);

        $authorizerMock = $this->createMock(PaymentAuthorizerInterface::class);
        $authorizerMock->expects($this->once())
            ->method('authorizePayment')
            ->with($this->callback(
                fn (OrderTransfer $order): bool => $order->getOrderReference() === static::ORDER_REFERENCE,
            ));

        // Act
        $this->createHandler(authorizer: $authorizerMock)->authorize($orderTransfer);
    }

    public function testCaptureDelegatesToCapturerWithAmountZeroByDefault(): void
    {
        // Arrange
        $capturerMock = $this->createMock(PaymentCapturerInterface::class);
        $capturerMock->expects($this->once())
            ->method('capturePayment')
            ->with(
                $this->isInstanceOf(OrderTransfer::class),
                $this->equalTo(0),
            );

        // Act
        $this->createHandler(capturer: $capturerMock)->capture(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );
    }

    public function testCaptureDelegatesToCapturerWithExplicitAmount(): void
    {
        // Arrange
        $capturerMock = $this->createMock(PaymentCapturerInterface::class);
        $capturerMock->expects($this->once())
            ->method('capturePayment')
            ->with(
                $this->isInstanceOf(OrderTransfer::class),
                $this->equalTo(9999),
            );

        // Act
        $this->createHandler(capturer: $capturerMock)->capture(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            9999,
        );
    }

    public function testCancelDelegatesToCancellerWithCorrectOrder(): void
    {
        // Arrange
        $orderTransfer = (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE);

        $cancellerMock = $this->createMock(PaymentCancellerInterface::class);
        $cancellerMock->expects($this->once())
            ->method('cancelPayment')
            ->with($this->callback(
                fn (OrderTransfer $order): bool => $order->getOrderReference() === static::ORDER_REFERENCE,
            ));

        // Act
        $this->createHandler(canceller: $cancellerMock)->cancel($orderTransfer);
    }

    public function testRefundDelegatesToRefunderWithOrderItemsAndAmount(): void
    {
        // Arrange
        $orderItems = [
            (new ItemTransfer())->setSku('SKU-001'),
            (new ItemTransfer())->setSku('SKU-002'),
        ];

        $refunderMock = $this->createMock(PaymentRefunderInterface::class);
        $refunderMock->expects($this->once())
            ->method('refundPayment')
            ->with(
                $this->isInstanceOf(OrderTransfer::class),
                $this->equalTo($orderItems),
                $this->equalTo(5000),
            );

        // Act
        $this->createHandler(refunder: $refunderMock)->refund(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            $orderItems,
            5000,
        );
    }

    public function testRefundDelegatesToRefunderWithZeroAmountByDefault(): void
    {
        // Arrange
        $refunderMock = $this->createMock(PaymentRefunderInterface::class);
        $refunderMock->expects($this->once())
            ->method('refundPayment')
            ->with(
                $this->isInstanceOf(OrderTransfer::class),
                $this->equalTo([]),
                $this->equalTo(0),
            );

        // Act
        $this->createHandler(refunder: $refunderMock)->refund(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            [],
        );
    }

    protected function createHandler(
        PaymentAuthorizerInterface|null $authorizer = null,
        PaymentCapturerInterface|null $capturer = null,
        PaymentCancellerInterface|null $canceller = null,
        PaymentRefunderInterface|null $refunder = null,
    ): OmsCommandHandler {
        return new OmsCommandHandler(
            $authorizer ?? $this->createMock(PaymentAuthorizerInterface::class),
            $capturer ?? $this->createMock(PaymentCapturerInterface::class),
            $canceller ?? $this->createMock(PaymentCancellerInterface::class),
            $refunder ?? $this->createMock(PaymentRefunderInterface::class),
        );
    }
}
