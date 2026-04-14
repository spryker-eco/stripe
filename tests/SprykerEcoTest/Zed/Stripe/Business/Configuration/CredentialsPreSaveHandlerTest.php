<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Configuration;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueDeletionTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Generated\Shared\Transfer\StripeApiCredentialsValidationTransfer;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Handler\CredentialsPreSaveHandler;
use SprykerEco\Zed\Stripe\Business\Validator\ApiCredentialsValidatorInterface;
use SprykerEco\Zed\Stripe\Communication\Constraint\StripeCredentialsConstraint;
use SprykerEco\Zed\Stripe\Communication\Constraint\StripeCredentialsMissingConstraint;
use SprykerEco\Zed\Stripe\StripeConfig;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Configuration
 * @group CredentialsPreSaveHandlerTest
 */
class CredentialsPreSaveHandlerTest extends Unit
{
    protected const string VALID_SECRET_KEY = 'sk_test_secret';

    protected const string VALID_PUBLISHABLE_KEY = 'pk_test_publishable';

    protected const string VALID_WEBHOOK_SECRET = 'whsec_webhook';

    protected const string VALID_WEBHOOK_SECRET_CONNECT = 'whsec_connect';

    public function testHandleCredentialsPreSaveReturnsEarlyWhenNoCredentialFieldsInRequest(): void
    {
        // Arrange
        $apiValidatorMock = $this->createMock(ApiCredentialsValidatorInterface::class);
        $apiValidatorMock->expects($this->never())->method('validate');

        $handler = $this->createHandler($apiValidatorMock);

        $request = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())->setSettingKey('some_other:key')->setValue('value'),
            );

        // Act
        $result = $handler->handleCredentialsPreSave($request);

        // Assert
        $this->assertSame('value', $result->getConfigurationValues()[0]->getValue());
    }

    public function testHandleCredentialsPreSaveReturnsEarlyWhenAllCredentialsAreEmpty(): void
    {
        // Arrange
        $apiValidatorMock = $this->createMock(ApiCredentialsValidatorInterface::class);
        $apiValidatorMock->expects($this->never())->method('validate');

        $handler = $this->createHandler($apiValidatorMock, secretKey: '', publishableKey: '', webhookSecret: '', webhookSecretConnect: '');

        $request = $this->buildRequest(secretKey: '', publishableKey: '', webhookSecret: '', webhookSecretConnect: '');

        // Act
        $result = $handler->handleCredentialsPreSave($request);

        // Assert
        foreach ($result->getConfigurationValues() as $value) {
            $this->assertNotSame(StripeCredentialsMissingConstraint::INVALID_SENTINEL, $value->getValue());
            $this->assertNotSame(StripeCredentialsConstraint::INVALID_SENTINEL, $value->getValue());
        }
    }

    public function testHandleCredentialsPreSaveMarksMissingRequiredFieldsWithMissingSentinel(): void
    {
        // Arrang
        $apiValidatorMock = $this->createMock(ApiCredentialsValidatorInterface::class);
        $apiValidatorMock->expects($this->never())->method('validate');

        $handler = $this->createHandler($apiValidatorMock);

        $request = $this->buildRequest(publishableKey: '', webhookSecret: '');

        // Act
        $result = $handler->handleCredentialsPreSave($request);

        // Assert
        $valuesByKey = $this->indexValuesBySettingKey($result);

        $this->assertSame(StripeCredentialsMissingConstraint::INVALID_SENTINEL, $valuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY]);
        $this->assertSame(StripeCredentialsMissingConstraint::INVALID_SENTINEL, $valuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET]);
        $this->assertNotSame(StripeCredentialsMissingConstraint::INVALID_SENTINEL, $valuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY]);
    }

    public function testHandleCredentialsPreSaveDoesNotMarkWebhookSecretConnectAsMissing(): void
    {
        // Arrange
        $apiValidatorMock = $this->createApiValidatorMock(allValid: true);

        $handler = $this->createHandler($apiValidatorMock);

        $request = $this->buildRequest(webhookSecretConnect: '');

        // Act
        $result = $handler->handleCredentialsPreSave($request);

        // Assert
        $valuesByKey = $this->indexValuesBySettingKey($result);

        $this->assertNotSame(StripeCredentialsMissingConstraint::INVALID_SENTINEL, $valuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT] ?? '');
    }

    public function testHandleCredentialsPreSavePassesWhenAllCredentialsAreValid(): void
    {
        // Arrange
        $apiValidatorMock = $this->createApiValidatorMock(allValid: true);
        $handler = $this->createHandler($apiValidatorMock);

        $request = $this->buildRequest();

        // Act
        $result = $handler->handleCredentialsPreSave($request);

        // Assert
        foreach ($result->getConfigurationValues() as $value) {
            $this->assertNotSame(StripeCredentialsConstraint::INVALID_SENTINEL, $value->getValue());
        }
    }

    public function testHandleCredentialsPreSaveMarksInvalidSecretKeyWithInvalidSentinel(): void
    {
        // Arrange
        $validationTransfer = (new StripeApiCredentialsValidationTransfer())
            ->setIsSecretKeyValid(false)
            ->setIsPublishableKeyValid(true)
            ->setIsWebhookSecretValid(true)
            ->setIsWebhookSecretConnectValid(true);

        $apiValidatorMock = $this->createMock(ApiCredentialsValidatorInterface::class);
        $apiValidatorMock->method('validate')->willReturn($validationTransfer);

        $handler = $this->createHandler($apiValidatorMock);

        $request = $this->buildRequest();

        // Act
        $result = $handler->handleCredentialsPreSave($request);

        // Assert
        $valuesByKey = $this->indexValuesBySettingKey($result);

        $this->assertSame(StripeCredentialsConstraint::INVALID_SENTINEL, $valuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY]);
        $this->assertNotSame(StripeCredentialsConstraint::INVALID_SENTINEL, $valuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY]);
    }

    public function testHandleCredentialsPreSaveNormalizesDeletionKeyToEmptyValue(): void
    {
        // Arrange
        $apiValidatorMock = $this->createApiValidatorMock(allValid: true);
        $handler = $this->createHandler($apiValidatorMock);

        $deletionKey = (new ConfigurationValueDeletionTransfer())
            ->setSettingKey(SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT);

        $request = $this->buildRequest()
            ->addDeletionKey($deletionKey);

        // Act
        $result = $handler->handleCredentialsPreSave($request);

        // Assert
        $this->assertEmpty($result->getDeletionKeys());

        $valuesByKey = $this->indexValuesBySettingKey($result);
        $this->assertSame('', $valuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT]);
    }

    protected function createHandler(
        ApiCredentialsValidatorInterface $apiValidatorMock,
        string $secretKey = self::VALID_SECRET_KEY,
        string $publishableKey = self::VALID_PUBLISHABLE_KEY,
        string $webhookSecret = self::VALID_WEBHOOK_SECRET,
        string $webhookSecretConnect = self::VALID_WEBHOOK_SECRET_CONNECT,
    ): CredentialsPreSaveHandler {
        $configMock = $this->createMock(StripeConfig::class);
        $configMock->method('getSecretKey')->willReturn($secretKey);
        $configMock->method('getPublishableKey')->willReturn($publishableKey);
        $configMock->method('getWebhookSecret')->willReturn($webhookSecret);
        $configMock->method('getWebhookConnectSecret')->willReturn($webhookSecretConnect);

        return new CredentialsPreSaveHandler($configMock, $apiValidatorMock);
    }

    protected function createApiValidatorMock(bool $allValid): ApiCredentialsValidatorInterface
    {
        $validationTransfer = (new StripeApiCredentialsValidationTransfer())
            ->setIsSecretKeyValid($allValid)
            ->setIsPublishableKeyValid($allValid)
            ->setIsWebhookSecretValid($allValid)
            ->setIsWebhookSecretConnectValid($allValid);

        $mock = $this->createMock(ApiCredentialsValidatorInterface::class);
        $mock->method('validate')->willReturn($validationTransfer);

        return $mock;
    }

    protected function buildRequest(
        string $secretKey = self::VALID_SECRET_KEY,
        string $publishableKey = self::VALID_PUBLISHABLE_KEY,
        string $webhookSecret = self::VALID_WEBHOOK_SECRET,
        string $webhookSecretConnect = self::VALID_WEBHOOK_SECRET_CONNECT,
    ): ConfigurationValueCollectionRequestTransfer {
        return (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey(SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY)
                    ->setValue($secretKey),
            )
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey(SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY)
                    ->setValue($publishableKey),
            )
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey(SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET)
                    ->setValue($webhookSecret),
            )
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey(SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT)
                    ->setValue($webhookSecretConnect),
            );
    }

    /**
     * @return array<string, string>
     */
    protected function indexValuesBySettingKey(ConfigurationValueCollectionRequestTransfer $requestTransfer): array
    {
        $indexed = [];

        foreach ($requestTransfer->getConfigurationValues() as $valueTransfer) {
            $indexed[$valueTransfer->getSettingKey()] = $valueTransfer->getValue();
        }

        return $indexed;
    }
}
