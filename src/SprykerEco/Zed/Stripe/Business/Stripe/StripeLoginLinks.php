<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use Stripe\Exception\ApiErrorException;

class StripeLoginLinks implements StripeLoginLinksInterface
{
    use LoggerTrait;

    public function __construct(protected StripeClientFactory $stripeClientFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $stripeAccountId): ?string
    {
        try {
            $loginLink = $this->stripeClientFactory->create()->accounts->createLoginLink($stripeAccountId);

            return $loginLink->url;
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error(
                sprintf('Failed to create Stripe Express Dashboard login link for account %s: %s', $stripeAccountId, $apiErrorException->getMessage()),
            );

            return null;
        }
    }
}
