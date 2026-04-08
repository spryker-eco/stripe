<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\Stripe\Business\Validator;

interface StripeWebhookEndpointCheckerInterface
{
    /**
     * @api
     *
     * Returns true when at least one webhook endpoint registered in the Stripe account
     * has a URL that exactly matches the provided endpoint URL.
     */
    public function isEndpointRegistered(string $secretKey, string $endpointUrl): bool;
}
