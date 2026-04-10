<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Validator;

use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use Stripe\Exception\ApiErrorException;

/**
 * @codeCoverageIgnore Infrastructure adapter — tested via integration tests.
 */
class StripeConnectionChecker implements StripeConnectionCheckerInterface
{
    public function __construct(protected StripeClientFactory $stripeClientFactory)
    {
    }

    public function check(string $secretKey): bool
    {
        try {
            $this->stripeClientFactory->create($secretKey)->balance->retrieve();

            return true;
        } catch (ApiErrorException) {
            return false;
        }
    }
}
