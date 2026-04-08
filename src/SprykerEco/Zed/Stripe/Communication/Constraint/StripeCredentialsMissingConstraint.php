<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Constraint;

use Symfony\Component\Validator\Constraint;

class StripeCredentialsMissingConstraint extends Constraint implements StripeSentinelConstraintInterface
{
    public const string INVALID_SENTINEL = '__STRIPE_CREDENTIAL_MISSING__';

    public string $message = 'This Stripe credential is required. All credentials must be provided together — partial configuration is not supported.';

    public function getInvalidSentinel(): string
    {
        return static::INVALID_SENTINEL;
    }

    public function validatedBy(): string
    {
        return StripeCredentialsSentinelConstraintValidator::class;
    }
}
