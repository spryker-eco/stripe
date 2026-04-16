<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Constraint;

use Symfony\Component\Validator\Constraint;

class StripeCredentialsConstraint extends Constraint implements StripeSentinelConstraintInterface
{
    public const string INVALID_SENTINEL = '__STRIPE_CREDENTIALS_INVALID__';

    public string $message = 'This Stripe credential is invalid. Please verify it in the Stripe Dashboard.';

    public function getInvalidSentinel(): string
    {
        return static::INVALID_SENTINEL;
    }

    public function validatedBy(): string
    {
        return StripeCredentialsSentinelConstraintValidator::class;
    }
}
