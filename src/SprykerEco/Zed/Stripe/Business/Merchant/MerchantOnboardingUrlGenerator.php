<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant;

use Generated\Shared\Transfer\StripeAccountLinksRequestTransfer;
use Generated\Shared\Transfer\StripeAccountRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeAccountLinks;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeAccounts;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class MerchantOnboardingUrlGenerator
{
    use LoggerTrait;

    public function __construct(
        protected StripeAccounts $stripeAccounts,
        protected StripeAccountLinks $stripeAccountLinks,
        protected StripeEntityManagerInterface $entityManager,
        protected StripeRepositoryInterface $repository,
        protected StripeConfig $config,
    ) {
    }

    /**
     * Generates a Stripe Connect onboarding URL for the given merchant.
     * Creates a connected account if none exists, then returns an account link URL.
     *
     * @param string $merchantReference
     * @param string $returnUrl URL Stripe redirects to after successful onboarding (falls back to config)
     * @param string $refreshUrl URL Stripe redirects to if the account link expires (falls back to config)
     *
     * @return string Stripe account link URL, or empty string on failure
     */
    public function generateOnboardingUrl(string $merchantReference, string $returnUrl, string $refreshUrl): string
    {
        $stripeAccountId = $this->resolveStripeAccountId($merchantReference);

        if ($stripeAccountId === null) {
            return '';
        }

        return $this->createAccountLink(
            $stripeAccountId,
            $returnUrl ?: $this->config->getMerchantOnboardingReturnUrl(),
            $refreshUrl ?: $this->config->getMerchantOnboardingRefreshUrl(),
        );
    }

    /**
     * Returns the existing Stripe account ID for the merchant, creating one if necessary.
     */
    protected function resolveStripeAccountId(string $merchantReference): ?string
    {
        $merchantTransfer = $this->repository->findMerchantByReference($merchantReference);

        if ($merchantTransfer !== null && $merchantTransfer->getStripeAccountId() !== null) {
            return $merchantTransfer->getStripeAccountId();
        }

        return $this->createStripeAccount($merchantReference);
    }

    /**
     * Creates a new Stripe Express connected account and persists the account ID.
     */
    protected function createStripeAccount(string $merchantReference): ?string
    {
        $accountResponse = $this->stripeAccounts->create(
            (new StripeAccountRequestTransfer())->setAccountConfig([
                'type' => 'express',
                'metadata' => [
                    StripeConfig::METADATA_MERCHANT_REFERENCE => $merchantReference,
                ],
            ]),
        );

        if (!$accountResponse->getIsSuccessful() || $accountResponse->getStripeAccount() === null) {
            $this->getLogger()->error(
                'Failed to create Stripe connected account',
                ['merchantReference' => $merchantReference, 'message' => $accountResponse->getMessage()],
            );

            return null;
        }

        $stripeAccountId = $accountResponse->getStripeAccount()->getAccountIdOrFail();
        $this->entityManager->saveMerchantStripeAccountId($merchantReference, $stripeAccountId);

        return $stripeAccountId;
    }

    /**
     * Creates a Stripe account link and returns its URL.
     */
    protected function createAccountLink(string $stripeAccountId, string $returnUrl, string $refreshUrl): string
    {
        $accountLinksResponse = $this->stripeAccountLinks->create(
            (new StripeAccountLinksRequestTransfer())->setAccountLinksConfig([
                'account' => $stripeAccountId,
                'return_url' => $returnUrl,
                'refresh_url' => $refreshUrl,
                'type' => 'account_onboarding',
            ]),
        );

        if (!$accountLinksResponse->getIsSuccessful()) {
            $this->getLogger()->error(
                'Failed to create Stripe account link',
                ['stripeAccountId' => $stripeAccountId, 'message' => $accountLinksResponse->getMessage()],
            );

            return '';
        }

        return (string)$accountLinksResponse->getUrl();
    }
}
