<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntents;
use SprykerEcoTest\Zed\Stripe\StripeBusinessTester;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Facade
 * @group GetPaymentDetailsTest
 */
class GetPaymentDetailsTest extends Unit
{
    protected const string OMS_PROCESS_NAME = 'Test01';

    protected const string CUSTOMER_REFERENCE = 'TEST--STRIPE-OWNER';

    protected const string OTHER_CUSTOMER_REFERENCE = 'TEST--STRIPE-OTHER';

    protected const string TRANSACTION_ID = 'pi_test_owner_check';

    protected const string CLIENT_SECRET = 'pi_test_owner_check_secret_abc';

    protected const string PUBLISHABLE_KEY = 'pk_test_owner_check';

    protected StripeBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->configureTestStateMachine([static::OMS_PROCESS_NAME]);
        $this->tester->mockConfigMethod('getPublishableKey', static::PUBLISHABLE_KEY);
    }

    /**
     * @dataProvider provideRequestsWithoutOrderOwnership
     */
    public function testGetPaymentDetailsReturnsBareNotFoundWhenRequesterDoesNotOwnOrder(
        ?string $customerReference,
        string $paymentIntentStatus,
    ): void {
        // Arrange
        $saveOrderTransfer = $this->haveOrderWithStripePayment();
        $this->mockStripeIntents($paymentIntentStatus, $this->never());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetails(
            $saveOrderTransfer->getOrderReferenceOrFail(),
            $customerReference,
        );

        // Assert
        $this->assertFalse($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertNull($stripeIntentResponseTransfer->getClientSecret());
        $this->assertNull($stripeIntentResponseTransfer->getPublishableKey());
        $this->assertNull($stripeIntentResponseTransfer->getStatus());
        $this->assertNull($stripeIntentResponseTransfer->getIdSalesOrder());
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function provideRequestsWithoutOrderOwnership(): array
    {
        return [
            'no customer reference' => [null, SharedStripeConfig::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD],
            'empty customer reference' => ['', SharedStripeConfig::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD],
            'another customer, open payment' => [static::OTHER_CUSTOMER_REFERENCE, SharedStripeConfig::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD],
            'another customer, paid order' => [static::OTHER_CUSTOMER_REFERENCE, SharedStripeConfig::PAYMENT_STATUS_SUCCEEDED],
        ];
    }

    public function testGetPaymentDetailsReturnsClientSecretToOrderOwnerWhilePaymentIsOpen(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveOrderWithStripePayment();
        $this->mockStripeIntents(SharedStripeConfig::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD, $this->once());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetails(
            $saveOrderTransfer->getOrderReferenceOrFail(),
            static::CUSTOMER_REFERENCE,
        );

        // Assert
        $this->assertTrue($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertSame(static::CLIENT_SECRET, $stripeIntentResponseTransfer->getClientSecret());
        $this->assertSame(static::PUBLISHABLE_KEY, $stripeIntentResponseTransfer->getPublishableKey());
        $this->assertSame($saveOrderTransfer->getIdSalesOrder(), $stripeIntentResponseTransfer->getIdSalesOrder());
    }

    public function testGetPaymentDetailsReturnsSucceededStatusToOrderOwnerWhenOrderIsPaid(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveOrderWithStripePayment();
        $this->mockStripeIntents(SharedStripeConfig::PAYMENT_STATUS_SUCCEEDED, $this->once());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetails(
            $saveOrderTransfer->getOrderReferenceOrFail(),
            static::CUSTOMER_REFERENCE,
        );

        // Assert
        $this->assertFalse($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertSame(SharedStripeConfig::PAYMENT_STATUS_SUCCEEDED, $stripeIntentResponseTransfer->getStatus());
    }

    protected function haveOrderWithStripePayment(): SaveOrderTransfer
    {
        $saveOrderTransfer = $this->tester->haveOrder(
            [CustomerTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE],
            static::OMS_PROCESS_NAME,
        );
        $this->tester->haveStripePayment($saveOrderTransfer, static::TRANSACTION_ID);

        return $saveOrderTransfer;
    }

    protected function mockStripeIntents(string $paymentIntentStatus, InvokedCount $invokedCount): void
    {
        $stripeIntentsMock = $this->createMock(StripeIntents::class);
        $stripeIntentsMock
            ->expects($invokedCount)
            ->method('get')
            ->willReturn(
                (new StripeIntentResponseTransfer())
                    ->setIsSuccessful(true)
                    ->setTransactionId(static::TRANSACTION_ID)
                    ->setClientSecret(static::CLIENT_SECRET)
                    ->setStatus($paymentIntentStatus)
                    ->setGrandTotal(1000)
                    ->setCurrencyCode('eur'),
            );

        $this->tester->mockFactoryMethod('createStripeIntents', $stripeIntentsMock);
    }
}
