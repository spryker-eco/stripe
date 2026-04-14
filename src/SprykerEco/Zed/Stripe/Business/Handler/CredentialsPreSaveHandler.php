<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Handler;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Validator\ApiCredentialsValidatorInterface;
use SprykerEco\Zed\Stripe\Communication\Constraint\StripeCredentialsConstraint;
use SprykerEco\Zed\Stripe\Communication\Constraint\StripeCredentialsMissingConstraint;
use SprykerEco\Zed\Stripe\StripeConfig;

class CredentialsPreSaveHandler implements CredentialsPreSaveHandlerInterface
{
    protected const string DEFAULT_CREDENTIAL_VALUE = '';

    public function __construct(
        protected StripeConfig $stripeConfig,
        protected ApiCredentialsValidatorInterface $apiCredentialsValidator,
    ) {
    }

    public function handleCredentialsPreSave(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
    ): ConfigurationValueCollectionRequestTransfer {
        $configurationValueCollectionRequestTransfer = $this->normalizeDeletionKeysToEmptyValues($configurationValueCollectionRequestTransfer);

        if (!$this->hasCredentialFieldsInRequest($configurationValueCollectionRequestTransfer)) {
            return $configurationValueCollectionRequestTransfer;
        }

        $credentialsBySettingKey = $this->buildCredentialsBySettingKey($configurationValueCollectionRequestTransfer);

        if ($this->areAllCredentialsEmpty($credentialsBySettingKey)) {
            return $configurationValueCollectionRequestTransfer;
        }

        $missingKeys = $this->getMissingCredentialKeys($credentialsBySettingKey);

        if ($missingKeys !== []) {
            return $this->markConfigurationValuesAsInvalid(
                $configurationValueCollectionRequestTransfer,
                StripeCredentialsMissingConstraint::INVALID_SENTINEL,
                $missingKeys,
            );
        }

        return $this->handleConfigurationValidation($configurationValueCollectionRequestTransfer, $credentialsBySettingKey);
    }

    protected function normalizeDeletionKeysToEmptyValues(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
    ): ConfigurationValueCollectionRequestTransfer {
        $deletionKeys = $configurationValueCollectionRequestTransfer->getDeletionKeys();
        $normalizedIndices = [];

        foreach ($deletionKeys as $index => $deletionKey) {
            if (
                $deletionKey->getSettingKey() === null
                || !in_array($deletionKey->getSettingKey(), StripeConfig::STRIPE_CREDENTIALS_KEYS, true)
            ) {
                continue;
            }

            $configurationValueCollectionRequestTransfer->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->fromArray($deletionKey->toArray(), true)
                    ->setValue(static::DEFAULT_CREDENTIAL_VALUE),
            );

            $normalizedIndices[] = $index;
        }

        foreach ($normalizedIndices as $index) {
            unset($deletionKeys[$index]);
        }

        return $configurationValueCollectionRequestTransfer->setDeletionKeys($deletionKeys);
    }

    protected function hasCredentialFieldsInRequest(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
    ): bool {
        foreach ($configurationValueCollectionRequestTransfer->getConfigurationValues() as $configurationValueTransfer) {
            if (in_array($configurationValueTransfer->getSettingKey(), StripeConfig::STRIPE_CREDENTIALS_KEYS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    protected function buildCredentialsBySettingKey(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
    ): array {
        $changeRequestValuesByKey = [];

        foreach ($configurationValueCollectionRequestTransfer->getConfigurationValues() as $configurationValueTransfer) {
            if ($configurationValueTransfer->getSettingKey() === null) {
                continue;
            }

            $changeRequestValuesByKey[$configurationValueTransfer->getSettingKey()] = $configurationValueTransfer->getValue() ?? '';
        }

        return [
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY => $changeRequestValuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY]
                ?? $this->stripeConfig->getSecretKey(),
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY => $changeRequestValuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY]
                ?? $this->stripeConfig->getPublishableKey(),
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET => $changeRequestValuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET]
                ?? $this->stripeConfig->getWebhookSecret(),
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT => $changeRequestValuesByKey[SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT]
                ?? $this->stripeConfig->getWebhookConnectSecret(),
        ];
    }

    /**
     * @param array<string, string> $credentialsBySettingKey
     */
    protected function areAllCredentialsEmpty(array $credentialsBySettingKey): bool
    {
        foreach ($credentialsBySettingKey as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, string> $credentialsBySettingKey
     *
     * @return array<string>
     */
    protected function getMissingCredentialKeys(array $credentialsBySettingKey): array
    {
        $requiredCredentialKeys = [
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY,
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY,
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET,
        ];

        $missingCredentialKeys = [];

        foreach ($requiredCredentialKeys as $key) {
            if (($credentialsBySettingKey[$key] ?? '') === '') {
                $missingCredentialKeys[] = $key;
            }
        }

        return $missingCredentialKeys;
    }

    /**
     * @param array<string, string> $credentialsBySettingKey
     */
    protected function handleConfigurationValidation(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
        array $credentialsBySettingKey,
    ): ConfigurationValueCollectionRequestTransfer {
        $validationTransfer = $this->apiCredentialsValidator->validate($credentialsBySettingKey);

        $validationResultByKey = [
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY => $validationTransfer->getIsSecretKeyValid(),
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY => $validationTransfer->getIsPublishableKeyValid(),
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET => $validationTransfer->getIsWebhookSecretValid(),
            SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT => $validationTransfer->getIsWebhookSecretConnectValid(),
        ];

        $invalidKeys = array_keys(array_filter($validationResultByKey, fn (?bool $isValid) => $isValid === false));

        return $this->markConfigurationValuesAsInvalid(
            $configurationValueCollectionRequestTransfer,
            StripeCredentialsConstraint::INVALID_SENTINEL,
            $invalidKeys,
        );
    }

    /**
     * @param array<string> $invalidKeys
     */
    protected function markConfigurationValuesAsInvalid(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
        string $sentinel,
        array $invalidKeys,
    ): ConfigurationValueCollectionRequestTransfer {
        foreach ($configurationValueCollectionRequestTransfer->getConfigurationValues() as $configurationValueTransfer) {
            if (in_array($configurationValueTransfer->getSettingKey(), $invalidKeys, true)) {
                $configurationValueTransfer->setValue($sentinel);
            }
        }

        return $configurationValueCollectionRequestTransfer;
    }
}
