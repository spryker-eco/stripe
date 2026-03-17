<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Payment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Generated\Shared\Transfer\StripeTransmissionResponseTransfer;
use Spryker\Zed\SalesPaymentMerchantExtension\Communication\Dependency\Plugin\MerchantPayoutCalculatorPluginInterface;
use SprykerEco\Zed\Stripe\Business\Merchant\Calculator\StripeMerchantPayoutAmountCalculatorFallback;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentFundsTransfer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfersInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
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

    protected const ITEM_PRICE = 1000;

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
            [$this->createItemTransfer(static::ITEM_PRICE)],
        );
    }

    public function testTransferSkipsWhenStripeIntentHasNoChargeId(): void
    {
        // Arrange — payment record exists but PaymentIntent has not been charged yet
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('get')
            ->willReturn((new StripeIntentResponseTransfer())->setIsSuccessful(true));

        $fundsTransfer = $this->createPaymentFundsTransfer($stripeTransfersMock, null, null, null, null, $stripeIntentsMock);

        // Act
        $fundsTransfer->transfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [$this->createItemTransfer(static::ITEM_PRICE)],
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
            [$this->createItemTransfer(static::ITEM_PRICE)],
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
            [$this->createItemTransfer(static::ITEM_PRICE)],
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
                    && $request->getAmount() === (string)static::ITEM_PRICE;
            }))
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true)->setTransferId('tr_test_001'));

        $entityManagerMock = $this->createMock(StripeEntityManagerInterface::class);
        $entityManagerMock->expects($this->once())->method('saveMerchantPayout');

        $fundsTransfer = $this->createPaymentFundsTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $this->createRepositoryMock(),
            $entityManagerMock,
        );

        // Act
        $fundsTransfer->transfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [$this->createItemTransfer(static::ITEM_PRICE)],
        );
    }

    public function testTransferSumsAmountsFromMultipleItemsViaCalculator(): void
    {
        // Arrange — two items for the same merchant; amounts are summed via the fallback calculator
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(fn($request): bool => $request->getAmount() === '1500'))
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
            [
                $this->createItemTransfer(600),
                $this->createItemTransfer(900),
            ],
        );
    }

    public function testTransferUsesCustomCalculatorPlugin(): void
    {
        // Arrange — custom plugin halves the price (simulates commission deduction)
        $calculatorMock = $this->createMock(MerchantPayoutCalculatorPluginInterface::class);
        $calculatorMock->method('calculatePayoutAmount')->willReturn(500);

        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(fn($request): bool => $request->getAmount() === '500'))
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true));

        $fundsTransfer = $this->createPaymentFundsTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $this->createRepositoryMock(),
            null,
            $calculatorMock,
        );

        // Act
        $fundsTransfer->transfer(
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
            static::MERCHANT_REFERENCE,
            [$this->createItemTransfer(static::ITEM_PRICE)],
        );
    }

    public function testTransferPersistsFailedPayoutRecord(): void
    {
        // Arrange — Stripe transfer fails; payout record must still be saved (is_successful=false)
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->method('transfer')
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(false)->setMessage('API error'));

        $entityManagerMock = $this->createMock(StripeEntityManagerInterface::class);
        $entityManagerMock->expects($this->once())
            ->method('saveMerchantPayout')
            ->with($this->callback(fn($payout): bool => $payout->getIsSuccessful() === false));

        $fundsTransfer = $this->createPaymentFundsTransfer(
            $stripeTransfersMock,
            $this->createPaymentReaderMock(),
            $this->createRepositoryMock(),
            $entityManagerMock,
        );

        // Act
        $fundsTransfer->transfer(
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
                ->setTransactionId(static::TRANSACTION_ID),
        );

        return $mock;
    }

    protected function createStripeIntentsMock(): StripeIntentsInterface
    {
        $mock = $this->createMock(StripeIntentsInterface::class);
        $mock->method('get')->willReturn(
            (new StripeIntentResponseTransfer())
                ->setIsSuccessful(true)
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

    protected function createItemTransfer(int $price): ItemTransfer
    {
        return (new ItemTransfer())->setSumPriceToPayAggregation($price);
    }

    protected function createPaymentFundsTransfer(
        StripeTransfersInterface $stripeTransfers,
        ?PaymentReaderInterface $paymentReader = null,
        ?StripeRepositoryInterface $repository = null,
        ?StripeEntityManagerInterface $entityManager = null,
        ?MerchantPayoutCalculatorPluginInterface $amountCalculator = null,
        ?StripeIntentsInterface $stripeIntents = null,
    ): PaymentFundsTransfer {
        return new PaymentFundsTransfer(
            $stripeTransfers,
            $stripeIntents ?? $this->createStripeIntentsMock(),
            $paymentReader ?? $this->createPaymentReaderMock(),
            $repository ?? $this->createRepositoryMock(),
            $entityManager ?? $this->createMock(StripeEntityManagerInterface::class),
            $amountCalculator ?? new StripeMerchantPayoutAmountCalculatorFallback(),
        );
    }
}
