<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\Stripe\Business\Validator;

use Codeception\Test\Unit;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Validator\ApiCredentialsValidator;
use SprykerEco\Zed\Stripe\Business\Validator\StripeConnectionCheckerInterface;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group Stripe
 * @group Business
 * @group Validator
 * @group ApiCredentialsValidatorTest
 */
class ApiCredentialsValidatorTest extends Unit
{
    protected const string VALID_SECRET_KEY_TEST = 'sk_test_abc123';

    protected const string VALID_SECRET_KEY_LIVE = 'sk_live_abc123';

    protected const string VALID_PUBLISHABLE_KEY_TEST = 'pk_test_abc123';

    protected const string VALID_PUBLISHABLE_KEY_LIVE = 'pk_live_abc123';

    protected const string VALID_WEBHOOK_SECRET = 'whsec_abc123';

    protected const string VALID_WEBHOOK_SECRET_CONNECT = 'whsec_connect_abc123';

    protected const string INVALID_SECRET_KEY = 'invalid_secret';

    protected const string INVALID_PUBLISHABLE_KEY = 'invalid_publishable';

    protected const string INVALID_WEBHOOK_SECRET = 'invalid_webhook';

    public function testValidateReturnsAllValidWhenCredentialsAreCorrect(): void
    {
        // Arrange
        $validator = $this->createValidator();
        $credentials = $this->buildCredentials();

        // Act
        $result = $validator->validate($credentials);

        // Assert
        $this->assertTrue($result->getIsSecretKeyValid());
        $this->assertTrue($result->getIsPublishableKeyValid());
        $this->assertTrue($result->getIsWebhookSecretValid());
        $this->assertTrue($result->getIsWebhookSecretConnectValid());
    }

    public function testValidateMarksSecretKeyInvalidWhenFormatIsWrong(): void
    {
        // Arrange
        $connectionCheckerMock = $this->createMock(StripeConnectionCheckerInterface::class);
        $connectionCheckerMock->expects($this->never())->method('check');

        $validator = $this->createValidator(connectionCheckerMock: $connectionCheckerMock);

        // Act
        $result = $validator->validate($this->buildCredentials(secretKey: static::INVALID_SECRET_KEY));

        // Assert
        $this->assertFalse($result->getIsSecretKeyValid());
    }

    public function testValidateMarksSecretKeyInvalidWhenApiCallFails(): void
    {
        // Arrange
        $validator = $this->createValidator(connectionCheckerReturns: false);

        // Act
        $result = $validator->validate($this->buildCredentials());

        // Assert
        $this->assertFalse($result->getIsSecretKeyValid());
    }

    public function testValidateMarksPublishableKeyInvalidWhenFormatIsWrong(): void
    {
        // Arrange
        $validator = $this->createValidator();

        // Act
        $result = $validator->validate($this->buildCredentials(publishableKey: static::INVALID_PUBLISHABLE_KEY));

        // Assert
        $this->assertFalse($result->getIsPublishableKeyValid());
    }

    public function testValidateMarksPublishableKeyInvalidOnEnvironmentMismatch(): void
    {
        // Arrange
        $validator = $this->createValidator();

        // Act
        $result = $validator->validate($this->buildCredentials(publishableKey: static::VALID_PUBLISHABLE_KEY_LIVE));

        // Assert
        $this->assertTrue($result->getIsSecretKeyValid());
        $this->assertFalse($result->getIsPublishableKeyValid());
    }

    public function testValidateMarksPublishableKeyInvalidOnReversedEnvironmentMismatch(): void
    {
        // Arrange
        $validator = $this->createValidator();

        // Act
        $result = $validator->validate($this->buildCredentials(secretKey: static::VALID_SECRET_KEY_LIVE));

        // Assert
        $this->assertTrue($result->getIsSecretKeyValid());
        $this->assertFalse($result->getIsPublishableKeyValid());
    }

    public function testValidateDoesNotCheckEnvironmentConsistencyWhenSecretKeyIsInvalid(): void
    {
        // Arrange
        $connectionCheckerMock = $this->createMock(StripeConnectionCheckerInterface::class);
        $connectionCheckerMock->expects($this->never())->method('check');

        $validator = $this->createValidator(connectionCheckerMock: $connectionCheckerMock);

        $credentials = $this->buildCredentials(
            secretKey: static::INVALID_SECRET_KEY,
            publishableKey: static::VALID_PUBLISHABLE_KEY_LIVE,
        );

        // Act
        $result = $validator->validate($credentials);

        // Assert
        $this->assertFalse($result->getIsSecretKeyValid());
        $this->assertTrue($result->getIsPublishableKeyValid());
    }

    public function testValidateMarksWebhookSecretInvalidWhenFormatIsWrong(): void
    {
        // Arrange
        $validator = $this->createValidator();

        // Act
        $result = $validator->validate($this->buildCredentials(webhookSecret: static::INVALID_WEBHOOK_SECRET));

        // Assert
        $this->assertFalse($result->getIsWebhookSecretValid());
    }

    public function testValidateMarksWebhookSecretConnectInvalidWhenFormatIsWrong(): void
    {
        // Arrange
        $validator = $this->createValidator();

        // Act
        $result = $validator->validate($this->buildCredentials(webhookSecretConnect: static::INVALID_WEBHOOK_SECRET));

        // Assert
        $this->assertFalse($result->getIsWebhookSecretConnectValid());
    }

    public function testValidateTreatsEmptyWebhookSecretConnectAsValid(): void
    {
        // Arrange
        $validator = $this->createValidator();

        // Act
        $result = $validator->validate($this->buildCredentials(webhookSecretConnect: ''));

        // Assert
        $this->assertTrue($result->getIsWebhookSecretConnectValid());
    }

    protected function createValidator(
        bool $connectionCheckerReturns = true,
        ?StripeConnectionCheckerInterface $connectionCheckerMock = null,
    ): ApiCredentialsValidator {
        if ($connectionCheckerMock === null) {
            $connectionCheckerMock = $this->createMock(StripeConnectionCheckerInterface::class);
            $connectionCheckerMock->method('check')->willReturn($connectionCheckerReturns);
        }

        return new ApiCredentialsValidator($connectionCheckerMock);
    }

    /**
     * @return array<string, string>
     */
    protected function buildCredentials(
        string $secretKey = self::VALID_SECRET_KEY_TEST,
        string $publishableKey = self::VALID_PUBLISHABLE_KEY_TEST,
        string $webhookSecret = self::VALID_WEBHOOK_SECRET,
        string $webhookSecretConnect = self::VALID_WEBHOOK_SECRET_CONNECT,
    ): array {
        return [
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY => $secretKey,
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY => $publishableKey,
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET => $webhookSecret,
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT => $webhookSecretConnect,
        ];
    }
}
