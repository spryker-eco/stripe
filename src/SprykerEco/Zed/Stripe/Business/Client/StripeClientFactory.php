<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Client;

use SprykerEco\Zed\Stripe\StripeConfig;
use Stripe\StripeClient;

class StripeClientFactory
{
    public function __construct(protected StripeConfig $config)
    {
    }

    /**
     * @codeCoverageIgnore
     */
    public function create(string $secretKey = ''): StripeClient
    {
        return new StripeClient([
            'api_key' => $secretKey ?: $this->config->getSecretKey(),
            'stripe_version' => '2023-10-16',
        ]);
    }
}
