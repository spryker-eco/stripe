<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\StripeAccountLinksRequestTransfer;
use Generated\Shared\Transfer\StripeAccountLinksResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use Stripe\Exception\ApiErrorException;

class StripeAccountLinks
{
    use LoggerTrait;

    public function __construct(protected StripeClientFactory $stripeClientFactory)
    {
    }

    public function create(StripeAccountLinksRequestTransfer $stripeAccountLinksRequestTransfer): StripeAccountLinksResponseTransfer
    {
        $stripeAccountLinksResponseTransfer = new StripeAccountLinksResponseTransfer();
        $stripeAccountLinksResponseTransfer->setIsSuccessful(false);

        try {
            $stripeClient = $this->stripeClientFactory->create();

            $stripeAccountLink = $stripeClient->accountLinks->create(
                $stripeAccountLinksRequestTransfer->getAccountLinksConfig(),
            );

            $stripeAccountLinksResponseTransfer->setIsSuccessful(true);
            $stripeAccountLinksResponseTransfer->setUrl($stripeAccountLink->url);
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error($apiErrorException, (array)$stripeAccountLinksRequestTransfer->getAccountLinksConfig());

            $stripeAccountLinksResponseTransfer->setMessage($apiErrorException->getMessage());
        }

        return $stripeAccountLinksResponseTransfer;
    }
}
