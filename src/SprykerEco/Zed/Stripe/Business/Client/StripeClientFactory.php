<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Client;

use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
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
    public function create(): StripeClient
    {
        return new StripeClient([
            'api_key' => $this->config->getSecretKey(),
            'stripe_version' => StripeConfig::STRIPE_API_VERSION,
        ]);
    }

    /**
     * Returns Stripe API request options for connected-account routing.
     * In the direct business model, charges are created on the connected account (acct_xxx).
     *
     * @return array<string, string>|null
     */
    public function getConnectedAccountOpts(): ?array
    {
        if ($this->config->getBusinessModel() === SharedStripeConfig::BUSINESS_MODEL_DIRECT) {
            return ['stripe_account' => $this->config->getAccountId()];
        }

        return null;
    }
}
