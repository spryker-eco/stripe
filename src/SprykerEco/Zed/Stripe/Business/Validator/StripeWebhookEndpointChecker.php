<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\Stripe\Business\Validator;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * @codeCoverageIgnore Infrastructure adapter — tested via integration tests.
 */
class StripeWebhookEndpointChecker implements StripeWebhookEndpointCheckerInterface
{
    protected const string STRIPE_API_VERSION = '2023-10-16';

    // Stripe returns at most 100 items per page; fetch the maximum to cover all registered endpoints.
    protected const int ENDPOINT_FETCH_LIMIT = 100;

    /**
     * {@inheritDoc}
     */
    public function isEndpointRegistered(string $secretKey, string $endpointUrl): bool
    {
        try {
            $client = new StripeClient([
                'api_key' => $secretKey,
                'stripe_version' => static::STRIPE_API_VERSION,
            ]);

            $endpoints = $client->webhookEndpoints->all(['limit' => static::ENDPOINT_FETCH_LIMIT]);

            foreach ($endpoints->data as $endpoint) {
                if ($endpoint->url === $endpointUrl) {
                    return true;
                }
            }

            return false;
        } catch (ApiErrorException) {
            return false;
        }
    }
}
