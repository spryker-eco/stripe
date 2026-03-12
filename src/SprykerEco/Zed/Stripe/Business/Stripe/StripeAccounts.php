<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\StripeAccountRequestTransfer;
use Generated\Shared\Transfer\StripeAccountResponseTransfer;
use Generated\Shared\Transfer\StripeAccountTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use Stripe\Exception\ApiErrorException;

class StripeAccounts implements StripeAccountsInterface
{
    use LoggerTrait;

    public function __construct(protected StripeClientFactory $stripeClientFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(StripeAccountRequestTransfer $stripeAccountRequestTransfer): StripeAccountResponseTransfer
    {
        $stripeAccountResponseTransfer = new StripeAccountResponseTransfer();
        $stripeAccountResponseTransfer->setIsSuccessful(false);

        try {
            $stripeClient = $this->stripeClientFactory->create();

            $stripeAccount = $stripeClient->accounts->create(
                $stripeAccountRequestTransfer->getAccountConfig(),
            );

            $stripeAccountResponseTransfer->setIsSuccessful(true);
            $stripeAccountResponseTransfer->setStripeAccount(
                (new StripeAccountTransfer())->setAccountId($stripeAccount->id),
            );
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error($apiErrorException, (array)$stripeAccountRequestTransfer->getAccountConfig());

            $stripeAccountResponseTransfer->setMessage($apiErrorException->getMessage());
        }

        return $stripeAccountResponseTransfer;
    }
}
