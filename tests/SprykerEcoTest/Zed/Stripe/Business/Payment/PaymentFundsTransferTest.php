<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Payment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Generated\Shared\Transfer\StripeTransmissionResponseTransfer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentFundsTransfer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfersInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Payment
 * @group PaymentFundsTransferTest
 */
class PaymentFundsTransferTest extends Unit
{
    protected const ORDER_REFERENCE = 'DE--001';

    protected const TRANSACTION_ID = 'pi_test_123';

    protected const CHARGE_ID = 'ch_test_abc';

    protected const MERCHANT_REFERENCE = 'MER-001';

    protected const STRIPE_ACCOUNT_ID = 'acct_test_xyz';

    public function testTransferSkipsWhenNoPaymentFound(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')->willReturn(null);

        $fundsTransfer = $this->createPaymentFundsTransfer($stripeTransfersMock, $paymentReaderMock);

        // Act
        $fundsTransfer->transfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            1000,
        );
    }

    public function testTransferSkipsWhenLatestChargeIdIsMissing(): void
    {
        // Arrange — payment record exists but has no latest_charge_id (PaymentIntent not yet charged)
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')
            ->willReturn(
                (new StripePaymentTransfer())
                    ->setOrderReference(static::ORDER_REFERENCE)
                    ->setTransactionId(static::TRANSACTION_ID),
            );

        $fundsTransfer = $this->createPaymentFundsTransfer($stripeTransfersMock, $paymentReaderMock);

        // Act
        $fundsTransfer->transfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            1000,
        );
    }

    public function testTransferSkipsWhenMerchantNotFoundInRepository(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $repositoryMock = $this->createMock(StripeRepositoryInterface::class);
        $repositoryMock->method('findMerchantByReference')->willReturn(null);

        $fundsTransfer = $this->createPaymentFundsTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $repositoryMock,
        );

        // Act
        $fundsTransfer->transfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            1000,
        );
    }

    public function testTransferSkipsWhenMerchantHasNoStripeAccountId(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $repositoryMock = $this->createMock(StripeRepositoryInterface::class);
        $repositoryMock->method('findMerchantByReference')
            ->willReturn((new StripeMerchantTransfer())->setMerchantReference(static::MERCHANT_REFERENCE));

        $fundsTransfer = $this->createPaymentFundsTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $repositoryMock,
        );

        // Act
        $fundsTransfer->transfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            1000,
        );
    }

    public function testTransferCallsStripeWithCorrectSourceTransactionAndDestination(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(function ($request): bool {
                return $request->getSourceTransaction() === static::CHARGE_ID
                    && $request->getDestination() === static::STRIPE_ACCOUNT_ID
                    && $request->getTransferGroup() === static::ORDER_REFERENCE
                    && $request->getAmount() === '1000';
            }))
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true));

        $fundsTransfer = $this->createPaymentFundsTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $this->createRepositoryMock(),
        );

        // Act
        $fundsTransfer->transfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            1000,
        );
    }

    protected function createPaymentReaderMock(): PaymentReaderInterface
    {
        $mock = $this->createMock(PaymentReaderInterface::class);
        $mock->method('getPaymentByOrderReference')->willReturn(
            (new StripePaymentTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->setTransactionId(static::TRANSACTION_ID)
                ->setLatestChargeId(static::CHARGE_ID)
                ->setCurrencyCode('EUR'),
        );

        return $mock;
    }

    protected function createRepositoryMock(): StripeRepositoryInterface
    {
        $mock = $this->createMock(StripeRepositoryInterface::class);
        $mock->method('findMerchantByReference')->willReturn(
            (new StripeMerchantTransfer())
                ->setMerchantReference(static::MERCHANT_REFERENCE)
                ->setStripeAccountId(static::STRIPE_ACCOUNT_ID),
        );

        return $mock;
    }

    protected function createPaymentFundsTransfer(
        StripeTransfersInterface $stripeTransfers,
        PaymentReaderInterface $paymentReader,
        ?StripeRepositoryInterface $repository = null,
    ): PaymentFundsTransfer {
        return new PaymentFundsTransfer(
            $stripeTransfers,
            $paymentReader,
            $repository ?? $this->createRepositoryMock(),
        );
    }
}
