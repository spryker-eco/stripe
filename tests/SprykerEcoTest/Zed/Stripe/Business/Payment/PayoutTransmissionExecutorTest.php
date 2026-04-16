<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Payment;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransmissionItemTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;
use Generated\Shared\Transfer\StripeTransmissionResponseTransfer;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PayoutTransmissionExecutor;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfersInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Payment
 * @group PayoutTransmissionExecutorTest
 */
class PayoutTransmissionExecutorTest extends Unit
{
    protected const string ORDER_REFERENCE = 'DE--001';

    protected const string TRANSACTION_ID = 'pi_test_123';

    protected const string CHARGE_ID = 'ch_test_abc';

    protected const string MERCHANT_REFERENCE = 'MER-001';

    protected const string MERCHANT_REFERENCE_B = 'MER-002';

    protected const string STRIPE_ACCOUNT_ID = 'acct_test_xyz';

    protected const string STRIPE_ACCOUNT_ID_B = 'acct_test_yyy';

    protected const string TRANSFER_ID = 'tr_test_001';

    protected const int ITEM_AMOUNT = 1000;

    public function testExecutionSkipsWhenNoPaymentFound(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $paymentReaderMock = $this->createMock(PaymentReaderInterface::class);
        $paymentReaderMock->method('getPaymentByOrderReference')->willReturn(null);

        $executor = $this->createExecutor($stripeTransfersMock, $paymentReaderMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, static::ITEM_AMOUNT)],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertEmpty($result->getPaymentTransmissions());
    }

    public function testExecutionSkipsWhenIntentHasNoChargeId(): void
    {
        // Arrange — payment record exists but PaymentIntent has no charge yet
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $stripeIntentsMock = $this->createMock(StripeIntentsInterface::class);
        $stripeIntentsMock->method('get')
            ->willReturn((new StripeIntentResponseTransfer())->setIsSuccessful(true));

        $executor = $this->createExecutor($stripeTransfersMock, null, null, $stripeIntentsMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, static::ITEM_AMOUNT)],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertEmpty($result->getPaymentTransmissions());
    }

    public function testExecutionMarksResponseFailedWhenMerchantNotFoundInRepository(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $repositoryMock = $this->createMock(StripeRepositoryInterface::class);
        $repositoryMock->method('findMerchantByReference')->willReturn(null);

        $executor = $this->createExecutor($stripeTransfersMock, null, $repositoryMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, static::ITEM_AMOUNT)],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertCount(1, $result->getPaymentTransmissions());
        $this->assertFalse($result->getPaymentTransmissions()->offsetGet(0)->getIsSuccessful());
    }

    public function testExecutionMarksResponseFailedWhenMerchantHasNoStripeAccount(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $repositoryMock = $this->createMock(StripeRepositoryInterface::class);
        $repositoryMock->method('findMerchantByReference')->willReturn(
            (new StripeMerchantTransfer())->setMerchantReference(static::MERCHANT_REFERENCE),
        );

        $executor = $this->createExecutor($stripeTransfersMock, null, $repositoryMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, static::ITEM_AMOUNT)],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertCount(1, $result->getPaymentTransmissions());
        $this->assertFalse($result->getPaymentTransmissions()->offsetGet(0)->getIsSuccessful());
    }

    public function testForwardPayoutCallsStripeWithCorrectSourceTransactionAndDestination(): void
    {
        // Arrange
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(function ($request): bool {
                return $request->getSourceTransaction() === static::CHARGE_ID
                    && $request->getDestination() === static::STRIPE_ACCOUNT_ID
                    && $request->getTransferGroup() === static::ORDER_REFERENCE
                    && $request->getAmount() === (string)static::ITEM_AMOUNT;
            }))
            ->willReturn(
                (new StripeTransmissionResponseTransfer())->setIsSuccessful(true)->setTransferId(static::TRANSFER_ID),
            );

        $executor = $this->createExecutor($stripeTransfersMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, static::ITEM_AMOUNT, 'item-ref-1')],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertTrue($result->getPaymentTransmissions()->offsetGet(0)->getIsSuccessful());
        $this->assertSame(static::TRANSFER_ID, $result->getPaymentTransmissions()->offsetGet(0)->getTransferId());
    }

    public function testForwardPayoutSumsAmountsForSameMerchant(): void
    {
        // Arrange — two items for the same merchant; amounts must be summed into one transfer
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(fn ($request): bool => $request->getAmount() === '1500'))
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true));

        $executor = $this->createExecutor($stripeTransfersMock);

        // Act
        $executor->executePayoutTransmission(
            [
                $this->createItem(static::MERCHANT_REFERENCE, 600),
                $this->createItem(static::MERCHANT_REFERENCE, 900),
            ],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );
    }

    public function testForwardPayoutCreatesOneTransferPerMerchant(): void
    {
        // Arrange — items for two different merchants must result in two separate Stripe transfers
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->exactly(2))->method('transfer')
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true));

        $repositoryMock = $this->createMock(StripeRepositoryInterface::class);
        $repositoryMock->method('findMerchantByReference')->willReturnMap([
            [static::MERCHANT_REFERENCE, (new StripeMerchantTransfer())->setStripeAccountId(static::STRIPE_ACCOUNT_ID)],
            [static::MERCHANT_REFERENCE_B, (new StripeMerchantTransfer())->setStripeAccountId(static::STRIPE_ACCOUNT_ID_B)],
        ]);

        $executor = $this->createExecutor($stripeTransfersMock, null, $repositoryMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [
                $this->createItem(static::MERCHANT_REFERENCE, 500),
                $this->createItem(static::MERCHANT_REFERENCE_B, 700),
            ],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertCount(2, $result->getPaymentTransmissions());
    }

    public function testZeroAmountTransmissionIsSkippedWithoutCallingStripe(): void
    {
        // Arrange — items sum to zero (e.g. free shipment expense); Stripe must NOT be called
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->never())->method('transfer');

        $executor = $this->createExecutor($stripeTransfersMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, 0)],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert — response is marked successful (zero = nothing to transfer, not an error)
        $this->assertTrue($result->getPaymentTransmissions()->offsetGet(0)->getIsSuccessful());
    }

    public function testFailedForwardPayoutMarksResponseAsUnsuccessful(): void
    {
        // Arrange — Stripe transfer fails
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->method('transfer')
            ->willReturn(
                (new StripeTransmissionResponseTransfer())->setIsSuccessful(false)->setMessage('API error'),
            );

        $executor = $this->createExecutor($stripeTransfersMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, static::ITEM_AMOUNT)],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertFalse($result->getPaymentTransmissions()->offsetGet(0)->getIsSuccessful());
    }

    // ─── Reversal (negative amounts) ──────────────────────────────────────────

    public function testReversalCallsStripeWithNegativeAmountAndTransferIdFromItem(): void
    {
        // Arrange — negative amount + transferId on the item signals a reversal
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(function ($request): bool {
                return (int)$request->getAmount() === -static::ITEM_AMOUNT
                    && $request->getTransferId() === static::TRANSFER_ID;
            }))
            ->willReturn(
                (new StripeTransmissionResponseTransfer())->setIsSuccessful(true)->setTransferId('trr_test_001'),
            );

        $executor = $this->createExecutor($stripeTransfersMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, -static::ITEM_AMOUNT, 'item-ref-1', static::TRANSFER_ID)],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertTrue($result->getPaymentTransmissions()->offsetGet(0)->getIsSuccessful());
    }

    public function testReversalSumsNegativeAmountsForSameMerchant(): void
    {
        // Arrange — two items being reversed; amounts must be summed (both negative)
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->expects($this->once())
            ->method('transfer')
            ->with($this->callback(fn ($request): bool => (int)$request->getAmount() === -700))
            ->willReturn((new StripeTransmissionResponseTransfer())->setIsSuccessful(true));

        $executor = $this->createExecutor($stripeTransfersMock);

        // Act
        $executor->executePayoutTransmission(
            [
                $this->createItem(static::MERCHANT_REFERENCE, -300, 'ref-1', static::TRANSFER_ID),
                $this->createItem(static::MERCHANT_REFERENCE, -400, 'ref-2', static::TRANSFER_ID),
            ],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );
    }

    public function testFailedReversalMarksResponseAsUnsuccessful(): void
    {
        // Arrange — Stripe reversal API call fails
        $stripeTransfersMock = $this->createMock(StripeTransfersInterface::class);
        $stripeTransfersMock->method('transfer')
            ->willReturn(
                (new StripeTransmissionResponseTransfer())->setIsSuccessful(false)->setMessage('API error'),
            );

        $executor = $this->createExecutor($stripeTransfersMock);

        // Act
        $result = $executor->executePayoutTransmission(
            [$this->createItem(static::MERCHANT_REFERENCE, -static::ITEM_AMOUNT, 'ref-1', static::TRANSFER_ID)],
            (new OrderTransfer())->setOrderReference(static::ORDER_REFERENCE),
        );

        // Assert
        $this->assertFalse($result->getPaymentTransmissions()->offsetGet(0)->getIsSuccessful());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function createItem(
        string $merchantReference,
        int $amount,
        string $itemReference = 'item-ref',
        ?string $transferId = null,
    ): PaymentTransmissionItemTransfer {
        return (new PaymentTransmissionItemTransfer())
            ->setMerchantReference($merchantReference)
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setItemReference($itemReference)
            ->setAmount((string)$amount)
            ->setTransferId($transferId);
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

    protected function createExecutor(
        StripeTransfersInterface $stripeTransfers,
        ?PaymentReaderInterface $paymentReader = null,
        ?StripeRepositoryInterface $repository = null,
        ?StripeIntentsInterface $stripeIntents = null,
    ): PayoutTransmissionExecutor {
        return new PayoutTransmissionExecutor(
            $stripeTransfers,
            $stripeIntents ?? $this->createStripeIntentsMock(),
            $paymentReader ?? $this->createPaymentReaderMock(),
            $repository ?? $this->createRepositoryMock(),
        );
    }
}
