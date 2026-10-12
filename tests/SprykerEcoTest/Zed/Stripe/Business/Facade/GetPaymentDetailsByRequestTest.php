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
use Generated\Shared\Transfer\StripePaymentDetailsRequestTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
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
 * @group GetPaymentDetailsByRequestTest
 */
class GetPaymentDetailsByRequestTest extends Unit
{
    protected const string OMS_PROCESS_NAME = 'Test01';

    protected const string CUSTOMER_REFERENCE = 'TEST--STRIPE-OWNER';

    protected const string OTHER_CUSTOMER_REFERENCE = 'TEST--STRIPE-OTHER';

    protected const string TRANSACTION_ID = 'pi_test_request_check';

    protected const string CLIENT_SECRET = 'pi_test_request_check_secret_abc';

    protected const string WRONG_CLIENT_SECRET_OF_SAME_PAYMENT_INTENT = 'pi_test_request_check_secret_xyz';

    protected const string CLIENT_SECRET_OF_OTHER_PAYMENT_INTENT = 'pi_test_other_secret_abc';

    protected const string PUBLISHABLE_KEY = 'pk_test_request_check';

    protected StripeBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->configureTestStateMachine([static::OMS_PROCESS_NAME]);
        $this->tester->mockConfigMethod('getPublishableKey', static::PUBLISHABLE_KEY);
    }

    public function testGetPaymentDetailsByRequestReturnsPaymentDetailsForGuestOrderWhenClientSecretMatches(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveGuestOrderWithStripePayment();
        $this->mockStripeIntents($this->once());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setClientSecret(static::CLIENT_SECRET),
        );

        // Assert
        $this->assertTrue($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertSame(static::CLIENT_SECRET, $stripeIntentResponseTransfer->getClientSecret());
        $this->assertSame(static::PUBLISHABLE_KEY, $stripeIntentResponseTransfer->getPublishableKey());
    }

    public function testGetPaymentDetailsByRequestReturnsPaymentDetailsForGuestOrderWhenOrderIsPlacedInCurrentSession(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveGuestOrderWithStripePayment();
        $this->mockStripeIntents($this->once());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setIsOrderPlacedInCurrentSession(true),
        );

        // Assert
        $this->assertTrue($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertSame(static::CLIENT_SECRET, $stripeIntentResponseTransfer->getClientSecret());
        $this->assertSame(static::PUBLISHABLE_KEY, $stripeIntentResponseTransfer->getPublishableKey());
    }

    public function testGetPaymentDetailsByRequestReturnsBareNotFoundForGuestOrderWhenClientSecretOfSamePaymentIntentDoesNotMatch(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveGuestOrderWithStripePayment();
        $this->mockStripeIntents($this->once());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setClientSecret(static::WRONG_CLIENT_SECRET_OF_SAME_PAYMENT_INTENT),
        );

        // Assert
        $this->assertBareNotFound($stripeIntentResponseTransfer);
    }

    /**
     * @dataProvider provideClientSecretsThatAreNoProofForThePaymentIntent
     */
    public function testGetPaymentDetailsByRequestReturnsBareNotFoundForGuestOrderWithoutCallingStripeWhenRequestHasNoProof(
        ?string $clientSecret,
    ): void {
        // Arrange
        $saveOrderTransfer = $this->haveGuestOrderWithStripePayment();
        $this->mockStripeIntents($this->never());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setClientSecret($clientSecret)
                ->setIsOrderPlacedInCurrentSession(false),
        );

        // Assert
        $this->assertBareNotFound($stripeIntentResponseTransfer);
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function provideClientSecretsThatAreNoProofForThePaymentIntent(): array
    {
        return [
            'no client secret' => [null],
            'empty client secret' => [''],
            'client secret of another payment intent' => [static::CLIENT_SECRET_OF_OTHER_PAYMENT_INTENT],
            'payment intent id without secret part' => [static::TRANSACTION_ID],
        ];
    }

    public function testGetPaymentDetailsByRequestReturnsPaymentDetailsToRegisteredOrderOwner(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveRegisteredOrderWithStripePayment();
        $this->mockStripeIntents($this->once());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setCustomerReference(static::CUSTOMER_REFERENCE),
        );

        // Assert
        $this->assertTrue($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertSame(static::CLIENT_SECRET, $stripeIntentResponseTransfer->getClientSecret());
        $this->assertSame($saveOrderTransfer->getIdSalesOrder(), $stripeIntentResponseTransfer->getIdSalesOrder());
    }

    public function testGetPaymentDetailsByRequestReturnsBareNotFoundForRegisteredOrderWhenRequesterIsAnotherCustomer(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveRegisteredOrderWithStripePayment();
        $this->mockStripeIntents($this->never());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setCustomerReference(static::OTHER_CUSTOMER_REFERENCE)
                ->setIsOrderPlacedInCurrentSession(false),
        );

        // Assert
        $this->assertBareNotFound($stripeIntentResponseTransfer);
    }

    public function testGetPaymentDetailsByRequestReturnsPaymentDetailsToAnotherCustomerWhenClientSecretMatches(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveRegisteredOrderWithStripePayment();
        $this->mockStripeIntents($this->once());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setCustomerReference(static::OTHER_CUSTOMER_REFERENCE)
                ->setClientSecret(static::CLIENT_SECRET),
        );

        // Assert
        $this->assertTrue($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertSame(static::CLIENT_SECRET, $stripeIntentResponseTransfer->getClientSecret());
    }

    public function testGetPaymentDetailsByRequestReturnsPaymentDetailsToOrderOwnerWhenClientSecretDoesNotMatch(): void
    {
        // Arrange
        $saveOrderTransfer = $this->haveRegisteredOrderWithStripePayment();
        $this->mockStripeIntents($this->once());

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setCustomerReference(static::CUSTOMER_REFERENCE)
                ->setClientSecret(static::WRONG_CLIENT_SECRET_OF_SAME_PAYMENT_INTENT),
        );

        // Assert
        $this->assertTrue($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertSame(static::CLIENT_SECRET, $stripeIntentResponseTransfer->getClientSecret());
    }

    /**
     * @dataProvider providePaymentIntentStatuses
     */
    public function testGetPaymentDetailsByRequestMapsPaymentIntentStatusForGuestWithMatchingClientSecret(
        string $paymentIntentStatus,
        bool $expectedIsSuccessful,
        ?string $expectedStatus,
    ): void {
        // Arrange
        $saveOrderTransfer = $this->haveGuestOrderWithStripePayment();
        $this->mockStripeIntents($this->once(), $paymentIntentStatus);

        // Act
        $stripeIntentResponseTransfer = $this->tester->getFacade()->getPaymentDetailsByRequest(
            (new StripePaymentDetailsRequestTransfer())
                ->setOrderReference($saveOrderTransfer->getOrderReferenceOrFail())
                ->setClientSecret(static::CLIENT_SECRET),
        );

        // Assert
        $this->assertSame($expectedIsSuccessful, $stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertSame($expectedStatus, $stripeIntentResponseTransfer->getStatus());
    }

    /**
     * @return array<string, array{string, bool, string|null}>
     */
    public static function providePaymentIntentStatuses(): array
    {
        return [
            'succeeded' => [SharedStripeConfig::PAYMENT_STATUS_SUCCEEDED, false, SharedStripeConfig::PAYMENT_STATUS_SUCCEEDED],
            'requires capture' => [SharedStripeConfig::PAYMENT_STATUS_REQUIRES_CAPTURE, false, SharedStripeConfig::PAYMENT_STATUS_REQUIRES_CAPTURE],
            'canceled' => [SharedStripeConfig::PAYMENT_STATUS_CANCELED, false, SharedStripeConfig::PAYMENT_STATUS_CANCELED],
            'processing' => [SharedStripeConfig::PAYMENT_STATUS_PROCESSING, true, SharedStripeConfig::PAYMENT_STATUS_PROCESSING],
            'requires action' => [SharedStripeConfig::PAYMENT_STATUS_REQUIRES_ACTION, true, SharedStripeConfig::PAYMENT_STATUS_REQUIRES_ACTION],
            'requires confirmation' => [SharedStripeConfig::PAYMENT_STATUS_REQUIRES_CONFIRMATION, false, null],
        ];
    }

    protected function haveRegisteredOrderWithStripePayment(): SaveOrderTransfer
    {
        $saveOrderTransfer = $this->tester->haveOrder(
            [CustomerTransfer::CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE],
            static::OMS_PROCESS_NAME,
        );
        $this->tester->haveStripePayment($saveOrderTransfer, static::TRANSACTION_ID);

        return $saveOrderTransfer;
    }

    protected function haveGuestOrderWithStripePayment(): SaveOrderTransfer
    {
        $saveOrderTransfer = $this->tester->haveOrder(
            [
                CustomerTransfer::CUSTOMER_REFERENCE => null,
                CustomerTransfer::IS_GUEST => true,
            ],
            static::OMS_PROCESS_NAME,
        );
        $this->tester->haveStripePayment($saveOrderTransfer, static::TRANSACTION_ID);

        $this->assertNull(
            SpySalesOrderQuery::create()->findPk($saveOrderTransfer->getIdSalesOrderOrFail())->getCustomerReference(),
            'Precondition failed: the guest order must not have a customer reference.',
        );

        return $saveOrderTransfer;
    }

    protected function mockStripeIntents(
        InvokedCount $invokedCount,
        string $paymentIntentStatus = SharedStripeConfig::PAYMENT_STATUS_REQUIRES_PAYMENT_METHOD,
    ): void {
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

    protected function assertBareNotFound(StripeIntentResponseTransfer $stripeIntentResponseTransfer): void
    {
        $this->assertFalse($stripeIntentResponseTransfer->getIsSuccessful());
        $this->assertNull($stripeIntentResponseTransfer->getClientSecret());
        $this->assertNull($stripeIntentResponseTransfer->getPublishableKey());
        $this->assertNull($stripeIntentResponseTransfer->getStatus());
        $this->assertNull($stripeIntentResponseTransfer->getIdSalesOrder());
    }
}
