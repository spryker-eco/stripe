<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Validator;

use Generated\Shared\Transfer\StripeApiCredentialsValidationTransfer;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;

class ApiCredentialsValidator implements ApiCredentialsValidatorInterface
{
    protected const string SECRET_KEY_PREFIX = 'sk_';

    protected const string SECRET_KEY_TEST_PREFIX = 'sk_test_';

    protected const string PUBLISHABLE_KEY_PREFIX = 'pk_';

    protected const string PUBLISHABLE_KEY_TEST_PREFIX = 'pk_test_';

    protected const string WEBHOOK_SECRET_PREFIX = 'whsec_';

    public function __construct(
        protected StripeConnectionCheckerInterface $connectionChecker,
    ) {
    }

    /**
     * @param array<string, string> $credentialsBySettingKey
     */
    public function validate(array $credentialsBySettingKey): StripeApiCredentialsValidationTransfer
    {
        $secretKey = $credentialsBySettingKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY] ?? '';
        $publishableKey = $credentialsBySettingKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY] ?? '';
        $webhookSecret = $credentialsBySettingKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET] ?? '';
        $webhookSecretConnect = $credentialsBySettingKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT] ?? '';

        $isSecretKeyValid = $this->validateSecretKey($secretKey);
        $isPublishableKeyValid = $this->validatePublishableKey($publishableKey, $secretKey, $isSecretKeyValid);
        $isWebhookSecretValid = $this->validateWebhookSecret($webhookSecret);
        $isWebhookSecretConnectValid = $this->validateWebhookSecretConnect($webhookSecretConnect);

        return (new StripeApiCredentialsValidationTransfer())
            ->setIsSecretKeyValid($isSecretKeyValid)
            ->setIsPublishableKeyValid($isPublishableKeyValid)
            ->setIsWebhookSecretValid($isWebhookSecretValid)
            ->setIsWebhookSecretConnectValid($isWebhookSecretConnectValid);
    }

    protected function validateSecretKey(string $secretKey): bool
    {
        if (!str_starts_with($secretKey, static::SECRET_KEY_PREFIX)) {
            return false;
        }

        return $this->connectionChecker->check($secretKey);
    }

    protected function validatePublishableKey(string $publishableKey, string $secretKey, bool $isSecretKeyValid): bool
    {
        if (!str_starts_with($publishableKey, static::PUBLISHABLE_KEY_PREFIX)) {
            return false;
        }

        if ($isSecretKeyValid && !$this->areKeysInTheSameEnvironment($secretKey, $publishableKey)) {
            return false;
        }

        return true;
    }

    protected function validateWebhookSecret(string $webhookSecret): bool
    {
        return str_starts_with($webhookSecret, static::WEBHOOK_SECRET_PREFIX);
    }

    protected function validateWebhookSecretConnect(string $webhookSecretConnect): bool
    {
        if ($webhookSecretConnect === '') {
            return true;
        }

        return str_starts_with($webhookSecretConnect, static::WEBHOOK_SECRET_PREFIX);
    }

    protected function areKeysInTheSameEnvironment(string $secretKey, string $publishableKey): bool
    {
        $isSecretKeyTest = str_starts_with($secretKey, static::SECRET_KEY_TEST_PREFIX);
        $isPublishableKeyTest = str_starts_with($publishableKey, static::PUBLISHABLE_KEY_TEST_PREFIX);

        return $isSecretKeyTest === $isPublishableKeyTest;
    }
}
