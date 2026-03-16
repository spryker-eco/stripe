<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Stripe;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\StripeTransmissionRequestTransfer;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use SprykerEco\Zed\Stripe\Business\Message\MessageBuilder;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfers;
use SprykerEco\Zed\Stripe\StripeConfig;
use Stripe\Exception\ApiConnectionException;
use Stripe\Service\TransferService;
use Stripe\StripeClient;
use Stripe\Transfer;
use Stripe\TransferReversal;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Stripe
 * @group StripeTransfersTest
 */
class StripeTransfersTest extends Unit
{
    protected const ORDER_REFERENCE = 'DE--001';

    protected const MERCHANT_REFERENCE = 'MER-001';

    protected const TRANSACTION_ID = 'pi_test_123';

    protected const STRIPE_ACCOUNT_ID = 'acct_test_xyz';

    protected const CHARGE_ID = 'ch_test_abc';

    protected const TRANSFER_ID = 'tr_test_001';

    public function testPositiveTransferCallsCreateWithCorrectMetadata(): void
    {
        // Arrange
        $capturedTransferData = [];

        $transferServiceMock = Stub::make(TransferService::class, [
            'create' => function (array $data) use (&$capturedTransferData): Transfer {
                $capturedTransferData = $data;

                return Transfer::constructFrom(['id' => static::TRANSFER_ID]);
            },
        ]);

        $stripeTransfers = $this->createStripeTransfers($transferServiceMock);

        $request = $this->buildRequest(1000);

        // Act
        $response = $stripeTransfers->transfer($request);

        // Assert
        $this->assertTrue($response->getIsSuccessful());
        $this->assertSame(static::TRANSFER_ID, $response->getTransferId());

        $this->assertArrayHasKey('description', $capturedTransferData);
        $this->assertSame(
            MessageBuilder::transmissionRequestDescription(static::ORDER_REFERENCE, static::MERCHANT_REFERENCE),
            $capturedTransferData['description'],
        );

        $this->assertArrayHasKey('metadata', $capturedTransferData);
        $this->assertArrayHasKey(StripeConfig::METADATA_ORDER_REFERENCE, $capturedTransferData['metadata']);
        $this->assertSame(static::ORDER_REFERENCE, $capturedTransferData['metadata'][StripeConfig::METADATA_ORDER_REFERENCE]);
    }

    public function testNegativeAmountCallsCreateReversalInsteadOfCreate(): void
    {
        // Arrange
        $reversalCalled = false;

        $transferServiceMock = Stub::make(TransferService::class, [
            'create' => static function (): void {
                throw new \LogicException('create() should not be called for negative amounts');
            },
            'createReversal' => function (string $transferId, array $data) use (&$reversalCalled): TransferReversal {
                $reversalCalled = true;
                $this->assertSame(static::TRANSFER_ID, $transferId);
                $this->assertSame(500, $data['amount']);

                return TransferReversal::constructFrom(['id' => 'trr_test_001']);
            },
        ]);

        $stripeTransfers = $this->createStripeTransfers($transferServiceMock);

        // Negative amount with a previous transfer ID for the reversal
        $request = $this->buildRequest(-500, static::TRANSFER_ID);

        // Act
        $response = $stripeTransfers->transfer($request);

        // Assert
        $this->assertTrue($reversalCalled);
        $this->assertTrue($response->getIsSuccessful());
    }

    public function testNegativeAmountWithoutPreviousTransferIdReturnsFailure(): void
    {
        // Arrange
        $transferServiceMock = Stub::make(TransferService::class, [
            'create' => static function (): never {
                throw new \LogicException('Should not be reached');
            },
        ]);

        $stripeTransfers = $this->createStripeTransfers($transferServiceMock);

        $request = $this->buildRequest(-500); // no transferId

        // Act
        $response = $stripeTransfers->transfer($request);

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame(MessageBuilder::transferReversalDoesNotHaveAPreviousMadeTransfer(), $response->getMessage());
    }

    public function testApiExceptionReturnsFailureWithExceptionMessage(): void
    {
        // Arrange
        $transferServiceMock = Stub::make(TransferService::class, [
            'create' => static function (): void {
                throw new ApiConnectionException('There was an error when calling the Stripe API.');
            },
        ]);

        $stripeTransfers = $this->createStripeTransfers($transferServiceMock);

        // Act
        $response = $stripeTransfers->transfer($this->buildRequest(1000));

        // Assert
        $this->assertFalse($response->getIsSuccessful());
        $this->assertSame('There was an error when calling the Stripe API.', $response->getMessage());
        $this->assertNull($response->getTransferId());
    }

    protected function buildRequest(int $amount, ?string $transferId = null): StripeTransmissionRequestTransfer
    {
        $request = (new StripeTransmissionRequestTransfer())
            ->setAmount((string)$amount)
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->setDestination(static::STRIPE_ACCOUNT_ID)
            ->setDescription(MessageBuilder::transmissionRequestDescription(static::ORDER_REFERENCE, static::MERCHANT_REFERENCE))
            ->setSourceTransaction(static::CHARGE_ID)
            ->setTransferGroup(static::ORDER_REFERENCE)
            ->setMetadata([
                StripeConfig::METADATA_ORDER_REFERENCE => static::ORDER_REFERENCE,
                StripeConfig::METADATA_MERCHANT_REFERENCE => static::MERCHANT_REFERENCE,
            ]);

        if ($transferId !== null) {
            $request->setTransferId($transferId);
        }

        return $request;
    }

    protected function createStripeTransfers(TransferService $transferServiceMock): StripeTransfers
    {
        $stripeClientMock = Stub::make(StripeClient::class, ['transfers' => $transferServiceMock]);

        $factoryMock = $this->createMock(StripeClientFactory::class);
        $factoryMock->method('create')->willReturn($stripeClientMock);

        return new StripeTransfers($factoryMock);
    }
}
