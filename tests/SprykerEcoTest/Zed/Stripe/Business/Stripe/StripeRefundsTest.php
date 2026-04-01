<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Stripe;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\StripeRefundRequestTransfer;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeRefunds;
use SprykerEcoTest\Zed\Stripe\StripeBusinessTester;
use Stripe\Exception\ApiConnectionException;
use Stripe\Refund;
use Stripe\Service\RefundService;
use Stripe\StripeClient;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Stripe
 * @group StripeRefundsTest
 */
class StripeRefundsTest extends Unit
{
    protected const TRANSACTION_ID = 'pi_test_123';

    protected const REFUND_ID = 're_test_abc';

    protected StripeBusinessTester $tester;

    /**
     * @dataProvider refundStatusMappingProvider
     */
    public function testCreateReturnsSuccessfulResponseForValidStatus(
        string $stripeStatus,
        bool $expectedIsSuccessful,
    ): void {
        // Arrange
        $stripeRefunds = $this->createStripeRefundsWithResponse([
            'id' => static::REFUND_ID,
            'status' => $stripeStatus,
            'failure_reason' => null,
        ]);

        $request = (new StripeRefundRequestTransfer())
            ->setTransactionId(static::TRANSACTION_ID)
            ->setAmount(1000);

        // Act
        $response = $stripeRefunds->create($request);

        // Assert
        $this->assertSame($expectedIsSuccessful, $response->getIsSuccessful());
        $this->assertSame($stripeStatus, $response->getStatus());
        $this->assertSame(static::REFUND_ID, $response->getRefundId());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function refundStatusMappingProvider(): array
    {
        return [
            'succeeded' => [Refund::STATUS_SUCCEEDED, true],
            'pending' => [Refund::STATUS_PENDING, true],
            'failed' => [Refund::STATUS_FAILED, true],
            'canceled' => [Refund::STATUS_CANCELED, true],
            'requires_action' => [Refund::STATUS_REQUIRES_ACTION, true],
        ];
    }

    public function testCreateSetsFailureReasonAsMessageWhenStatusIsFailed(): void
    {
        // Arrange
        $failureReason = 'expired_or_canceled_card';

        $stripeRefunds = $this->createStripeRefundsWithResponse([
            'id' => static::REFUND_ID,
            'status' => Refund::STATUS_FAILED,
            'failure_reason' => $failureReason,
        ]);

        $request = (new StripeRefundRequestTransfer())
            ->setTransactionId(static::TRANSACTION_ID)
            ->setAmount(1000);

        // Act
        $response = $stripeRefunds->create($request);

        // Assert
        $this->assertTrue($response->getIsSuccessful());
        $this->assertSame($failureReason, $response->getMessage());
    }

    public function testCreateSetsFailureReasonAsMessageWhenStatusIsCanceled(): void
    {
        // Arrange
        $failureReason = 'merchant_request';

        $stripeRefunds = $this->createStripeRefundsWithResponse([
            'id' => static::REFUND_ID,
            'status' => Refund::STATUS_CANCELED,
            'failure_reason' => $failureReason,
        ]);

        $request = (new StripeRefundRequestTransfer())
            ->setTransactionId(static::TRANSACTION_ID)
            ->setAmount(1000);

        // Act
        $response = $stripeRefunds->create($request);

        // Assert
        $this->assertTrue($response->getIsSuccessful());
        $this->assertSame($failureReason, $response->getMessage());
    }

    public function testCreateReturnsFailureWhenResponseMissingId(): void
    {
        // Arrange
        $stripeRefunds = $this->createStripeRefundsWithResponse([
            'status' => Refund::STATUS_SUCCEEDED,
        ]);

        $request = (new StripeRefundRequestTransfer())
            ->setTransactionId(static::TRANSACTION_ID)
            ->setAmount(1000);

        // Act
        $response = $stripeRefunds->create($request);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertNotNull($response->getMessage());
    }

    public function testCreateReturnsFailureWhenResponseMissingStatus(): void
    {
        // Arrange
        $stripeRefunds = $this->createStripeRefundsWithResponse([
            'id' => static::REFUND_ID,
        ]);

        $request = (new StripeRefundRequestTransfer())
            ->setTransactionId(static::TRANSACTION_ID)
            ->setAmount(1000);

        // Act
        $response = $stripeRefunds->create($request);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertNotNull($response->getMessage());
    }

    public function testCreateReturnsFailureWhenStripeApiThrowsException(): void
    {
        // Arrange
        $refundServiceMock = Stub::make(RefundService::class, [
            'create' => static function (): void {
                throw new ApiConnectionException('Connection error');
            },
        ]);

        $stripeClientMock = Stub::make(StripeClient::class, ['refunds' => $refundServiceMock]);
        $stripeRefunds = $this->createStripeRefundsWithClient($stripeClientMock);

        $request = (new StripeRefundRequestTransfer())
            ->setTransactionId(static::TRANSACTION_ID)
            ->setAmount(1000);

        // Act
        $response = $stripeRefunds->create($request);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame('Connection error', $response->getMessage());
    }

    /**
     * @param array<string, mixed> $refundData
     */
    protected function createStripeRefundsWithResponse(array $refundData): StripeRefunds
    {
        $refund = Refund::constructFrom($refundData);

        $refundServiceMock = Stub::make(RefundService::class, [
            'create' => static fn (): Refund => $refund,
        ]);

        $stripeClientMock = Stub::make(StripeClient::class, ['refunds' => $refundServiceMock]);

        return $this->createStripeRefundsWithClient($stripeClientMock);
    }

    protected function createStripeRefundsWithClient(StripeClient $stripeClient): StripeRefunds
    {
        $factoryMock = $this->createMock(StripeClientFactory::class);
        $factoryMock->method('create')->willReturn($stripeClient);

        return new StripeRefunds($factoryMock, $this->tester->getFactory()->getUtilEncodingService());
    }
}
