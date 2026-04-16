<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class StripeCredentialsSentinelConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StripeSentinelConstraintInterface) {
            throw new UnexpectedTypeException($constraint, StripeSentinelConstraintInterface::class);
        }

        if ($value !== $constraint->getInvalidSentinel()) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
