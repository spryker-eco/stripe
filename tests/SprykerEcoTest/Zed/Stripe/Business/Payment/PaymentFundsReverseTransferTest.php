<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Payment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeMerchantPayoutTransfer;
use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Generated\Shared\Transfer\StripeTransmissionResponseTransfer;
use Spryker\Zed\SalesPaymentMerchantExtension\Communication\Dependency\Plugin\MerchantPayoutCalculatorPluginInterface;
use SprykerEco\Zed\Stripe\Business\Merchant\Calculator\StripeMerchantPayoutReverseAmountCalculatorFallback;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentFundsReverseTransfer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfersInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Payment
 * @group PaymentFundsReverseTransferTest
 */
class PaymentFundsReverseTransferTest extends Unit
{
    protected const string ORDER_REFERENCE = 'DE--001';

    protected const string TRANSACTION_ID = 'pi_test_123';

    protected const string CHARGE_ID = 'ch_test_abc';

    protected const string MERCHANT_REFERENCE = 'MER-001';

    protected const string STRIPE_ACCOUNT_ID = 'acct_test_xyz';

    protected const string TRANSFER_ID = 'tr_test_001';

    protected const int ITEM_PRICE = 1000;

    public function testReverseTransferSkipsWhenNoPaymentFound(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')->willReturn(null);

        $reverseTransfer = $this->createPaymentFundsReverseTransfer($stripeTransfersMock, $paymentReaderMock);

        // Act
        $reverseTransfer->reverseTransfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [$this->createItemTransfer(static::ITEM_PRICE)],
        );
    }

    public function testReverseTransferSkipsWhenNoPreviousSuccessfulPayoutFound(): void
    {
        // Arrange — no previous successful payout record exists
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $repositoryMock = $this->createMock(StripeRepositoryInterface::class);
        $repositoryMock->method('findMerchantByReference')->willReturn(
            (new StripeMerchantTransfer())
                ->setMerchantReference(static::MERCHANT_REFERENCE)
                ->setStripeAccountId(static::STRIPE_ACCOUNT_ID),
        );
        $repositoryMock->method('findSuccessfulMerchantPayoutByOrderReferenceAndMerchantReference')
            ->willReturn(null);

        $reverseTransfer = $this->createPaymentFundsReverseTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $repositoryMock,
        );

        // Act
        $reverseTransfer->reverseTransfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [$this->createItemTransfer(static::ITEM_PRICE)],
        );
    }

    public function testReverseTransferCallsStripeWithNegativeAmountAndPreviousTransferId(): void
    {
        // Arrange — successful path: Stripe createReversal() must be called
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(function ($request): bool {
                return (int)$request->getAmount() === -static::ITEM_PRICE
                    && $request->getTransferId() === static::TRANSFER_ID;
            }))
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true)->setTransferId('trr_test_001'));

        $entityManagerMock = $this->createMock(StripeEntityManagerInterface::class);
        $entityManagerMock->expects($this->once())
            ->method('saveMerchantPayout')
            ->with($this->callback(fn($payout): bool =>
                $payout->getIsSuccessful() === true
                && $payout->getIsReversed() === true
                && $payout->getAmount() === -static::ITEM_PRICE
            ));

        $reverseTransfer = $this->createPaymentFundsReverseTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $this->createRepositoryMock(),
            $entityManagerMock,
        );

        // Act
        $reverseTransfer->reverseTransfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [$this->createItemTransfer(static::ITEM_PRICE)],
        );
    }

    public function testReverseTransferPersistsFailedReversalRecord(): void
    {
        // Arrange — Stripe reversal fails; the record must be saved with is_successful=false
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->method('transfer')
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(false)->setMessage('API error'));

        $entityManagerMock = $this->createMock(StripeEntityManagerInterface::class);
        $entityManagerMock->expects($this->once())
            ->method('saveMerchantPayout')
            ->with($this->callback(fn($payout): bool =>
                $payout->getIsSuccessful() === false
                && $payout->getIsReversed() === true
            ));

        $reverseTransfer = $this->createPaymentFundsReverseTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $this->createRepositoryMock(),
            $entityManagerMock,
        );

        // Act
        $reverseTransfer->reverseTransfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [$this->createItemTransfer(static::ITEM_PRICE)],
        );
    }

    public function testReverseTransferSumsAmountsFromMultipleItems(): void
    {
        // Arrange — two items; fallback uses canceledAmount when set
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(fn($request): bool => (int)$request->getAmount() === -700))
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true));

        $reverseTransfer = $this->createPaymentFundsReverseTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $this->createRepositoryMock(),
        );

        // Act
        $reverseTransfer->reverseTransfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [
                $this->createItemTransfer(400, 300), // canceledAmount=300 takes priority
                $this->createItemTransfer(600, 400), // canceledAmount=400 takes priority
            ],
        );
    }

    public function testReverseTransferUsesCustomCalculatorPlugin(): void
    {
        // Arrange — custom plugin returns a fixed commission-adjusted amount
        $calculatorMock = $this->createMock(MerchantPayoutCalculatorPluginInterface::class);
        $calculatorMock->method('calculatePayoutAmount')->willReturn(450);

        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(fn($request): bool => (int)$request->getAmount() === -450))
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true));

        $reverseTransfer = $this->createPaymentFundsReverseTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $this->createRepositoryMock(),
            null,
            $calculatorMock,
        );

        // Act
        $reverseTransfer->reverseTransfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [$this->createItemTransfer(static::ITEM_PRICE)],
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
        $mock->method('findSuccessfulMerchantPayoutByOrderReferenceAndMerchantReference')->willReturn(
            (new StripeMerchantPayoutTransfer())
                ->setOrderReference(static::ORDER_REFERENCE)
                ->setMerchantReference(static::MERCHANT_REFERENCE)
                ->setTransferId(static::TRANSFER_ID)
                ->setIsSuccessful(true)
                ->setIsReversed(false),
        );

        return $mock;
    }

    protected function createItemTransfer(int $price, ?int $canceledAmount = null): ItemTransfer
    {
        $item = (new ItemTransfer())->setSumPriceToPayAggregation($price);

        if ($canceledAmount !== null) {
            $item->setCanceledAmount($canceledAmount);
        }

        return $item;
    }

    protected function createPaymentFundsReverseTransfer(
        StripeTransfersInterface $stripeTransfers,
        PaymentReaderInterface $paymentReader,
        ?StripeRepositoryInterface $repository = null,
        ?StripeEntityManagerInterface $entityManager = null,
        ?MerchantPayoutCalculatorPluginInterface $reverseAmountCalculator = null,
    ): PaymentFundsReverseTransfer {
        return new PaymentFundsReverseTransfer(
            $stripeTransfers,
            $paymentReader,
            $repository ?? $this->createRepositoryMock(),
            $entityManager ?? $this->createMock(StripeEntityManagerInterface::class),
            $reverseAmountCalculator ?? new StripeMerchantPayoutReverseAmountCalculatorFallback(),
        );
    }
}
