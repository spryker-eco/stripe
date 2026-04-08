<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Validator;

use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;

class CredentialsFormatValidator implements CredentialsFormatValidatorInterface
{
    protected const string SECRET_KEY_PREFIX = 'sk_';

    protected const string PUBLISHABLE_KEY_PREFIX = 'pk_';

    protected const string WEBHOOK_SECRET_PREFIX = 'whsec_';

    /**
     * @var array<string, string>
     */
    protected const array REQUIRED_PREFIXES_BY_SETTING_KEY = [
        SharedStripeConfig::CONFIGURATION_KEY_STRIPE_SECRET_KEY => self::SECRET_KEY_PREFIX,
        SharedStripeConfig::CONFIGURATION_KEY_STRIPE_PUBLISHABLE_KEY => self::PUBLISHABLE_KEY_PREFIX,
        SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET => self::WEBHOOK_SECRET_PREFIX,
        SharedStripeConfig::CONFIGURATION_KEY_STRIPE_WEBHOOK_SECRET_CONNECT => self::WEBHOOK_SECRET_PREFIX,
    ];

    /**
     * @param array<string, string> $credentialsBySettingKey
     *
     * @return array<string>
     */
    public function getInvalidSettingKeys(array $credentialsBySettingKey): array
    {
        $invalidKeys = [];

        foreach ($credentialsBySettingKey as $settingKey => $value) {
            $requiredPrefix = static::REQUIRED_PREFIXES_BY_SETTING_KEY[$settingKey] ?? null;

            if ($requiredPrefix === null) {
                continue;
            }

            if (!str_starts_with($value, $requiredPrefix)) {
                $invalidKeys[] = $settingKey;
            }
        }

        return $invalidKeys;
    }
}
