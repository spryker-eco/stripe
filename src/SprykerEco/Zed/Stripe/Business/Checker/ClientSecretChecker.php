<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Checker;

class ClientSecretChecker implements ClientSecretCheckerInterface
{
    protected const string CLIENT_SECRET_SEPARATOR = '_secret_';

    public function isClientSecretOfPaymentIntent(?string $clientSecret, string $transactionId): bool
    {
        if ($clientSecret === null || $transactionId === '') {
            return false;
        }

        return str_starts_with($clientSecret, $transactionId . static::CLIENT_SECRET_SEPARATOR);
    }

    public function isClientSecretEqual(?string $clientSecret, ?string $expectedClientSecret): bool
    {
        if ($clientSecret === null || $expectedClientSecret === null || $expectedClientSecret === '') {
            return false;
        }

        return hash_equals($expectedClientSecret, $clientSecret);
    }
}
