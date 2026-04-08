<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Validator;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * @codeCoverageIgnore Infrastructure adapter — tested via integration tests.
 */
class StripeConnectionChecker implements StripeConnectionCheckerInterface
{
    public function check(string $secretKey): bool
    {
        try {
            $client = new StripeClient([
                'api_key' => $secretKey,
                'stripe_version' => '2023-10-16',
            ]);
            $client->balance->retrieve();

            return true;
        } catch (ApiErrorException) {
            return false;
        }
    }
}
