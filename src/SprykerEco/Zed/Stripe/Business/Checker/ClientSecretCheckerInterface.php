<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Checker;

interface ClientSecretCheckerInterface
{
    public function isClientSecretOfPaymentIntent(?string $clientSecret, string $transactionId): bool;

    public function isClientSecretEqual(?string $clientSecret, ?string $expectedClientSecret): bool;
}
