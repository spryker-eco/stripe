<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant;

use Generated\Shared\Transfer\MerchantAppOnboardingStatusChangedTransfer;
use Generated\Shared\Transfer\StripeWebhookProcessResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\MerchantApp\Business\MerchantAppFacadeInterface;
use Spryker\Zed\MerchantApp\Business\MerchantAppOnboarding\MerchantAppOnboardingStatusInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Message\MessageBuilder;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use Stripe\Account;
use Stripe\Event;

class MerchantOnboardingHandler
{
    use LoggerTrait;

    public function __construct(
        protected MerchantAppFacadeInterface $merchantAppFacade,
        protected StripeEntityManagerInterface $entityManager,
    ) {
    }

    public function handleAccountUpdated(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer {
        /** @var \Stripe\Account $account */
        $account = $event->data->object;

        if (!($account instanceof Account)) {
            return $response->setIsSuccessful(true);
        }

        $merchantReference = $account->metadata['merchantReference'] ?? null;

        if ($merchantReference === null) {
            $this->getLogger()->warning('MerchantOnboardingHandler: merchantReference missing from Stripe account metadata', [
                'accountId' => $account->id,
            ]);

            return $response->setIsSuccessful(true);
        }

        $status = $this->resolveOnboardingStatus($account);

        $this->entityManager->saveMerchantStripeAccountId($merchantReference, $account->id);

        $statusChangedTransfer = (new MerchantAppOnboardingStatusChangedTransfer())
            ->setMerchantReference($merchantReference)
            ->setAppIdentifier(SharedStripeConfig::PAYMENT_PROVIDER_NAME)
            ->setStatus($status);

        $this->merchantAppFacade->handleMerchantAppOnboardingStatusChanged($statusChangedTransfer);

        return $response->setIsSuccessful(true);
    }

    protected function resolveOnboardingStatus(Account $account): string
    {
        /** @var array<string, mixed> $capabilities */
        $capabilities = (array)($account['capabilities'] ?? []);

        /** @var array<string, mixed> $requirements */
        $requirements = (array)($account['requirements'] ?? []);

        /** @var array<string> $currentlyDue */
        $currentlyDue = (array)($requirements['currently_due'] ?? []);

        /** @var array<string> $pastDue */
        $pastDue = (array)($requirements['past_due'] ?? []);

        /** @var array<string> $eventuallyDue */
        $eventuallyDue = (array)($requirements['eventually_due'] ?? []);

        $disabledReason = (string)($requirements['disabled_reason'] ?? '');

        if ($disabledReason !== '' && $disabledReason !== '0' && str_contains($disabledReason, 'rejected')) {
            $this->getLogger()->info(MessageBuilder::accountRejected(), ['disabledReason' => $disabledReason]);

            return MerchantAppOnboardingStatusInterface::REJECTED;
        }

        if ($account->charges_enabled && $account->payouts_enabled) {
            $pendingVerification = (array)($requirements['pending_verification'] ?? []);
            if (count($pendingVerification) > 0) {
                return MerchantAppOnboardingStatusInterface::PENDING;
            }

            if (!$disabledReason && count($currentlyDue) === 0) {
                if (!isset($capabilities['transfers']) || $capabilities['transfers'] !== 'active') {
                    return MerchantAppOnboardingStatusInterface::PENDING;
                }

                if (count($pastDue) > 0) {
                    return MerchantAppOnboardingStatusInterface::RESTRICTED;
                }

                if (count($eventuallyDue) === 0) {
                    return MerchantAppOnboardingStatusInterface::COMPLETED;
                }

                return MerchantAppOnboardingStatusInterface::ENABLED;
            }

            return MerchantAppOnboardingStatusInterface::RESTRICTED;
        }

        if (!$account->charges_enabled) {
            return MerchantAppOnboardingStatusInterface::RESTRICTED;
        }

        return MerchantAppOnboardingStatusInterface::RESTRICTED;
    }
}
