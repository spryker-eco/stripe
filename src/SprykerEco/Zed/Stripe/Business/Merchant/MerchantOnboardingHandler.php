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

/**
 * [MerchantOnboardingHandler::handleAccountUpdated()]
 * ↓ maps Stripe fields (payouts_enabled, details_submitted) → MerchantApp status constant
 * [MerchantAppFacade::handleMerchantAppOnboardingStatusChanged(MerchantAppOnboardingStatusChangedTransfer)]
 * ↓
 * [MerchantApp module] → spy_merchant_app_onboarding_status.status
 *
 * Status mapping: (Stripe fields → `MerchantAppOnboardingStatus` constants):
 *
 * | Stripe `account` fields | MerchantApp status |
 * |---------------------------|--------------------|
 * | `details_submitted=false` | `INCOMPLETE` |
 * | `details_submitted=true`, `payouts_enabled=false` | `PENDING` / `RESTRICTED` |
 * | `details_submitted=true`, `payouts_enabled=true` | `ENABLED` |
 * | `requirements.disabled_reason` set | `REJECTED` / `RESTRICTED` |
 */
class MerchantOnboardingHandler implements MerchantOnboardingHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected MerchantAppFacadeInterface $merchantAppFacade,
        protected StripeEntityManagerInterface $entityManager,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function handleAccountUpdated(
        StripeWebhookProcessResponseTransfer $response,
        Event $event,
    ): StripeWebhookProcessResponseTransfer {
        $account = $event->data->offsetGet('object');

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
            ->setStatus($status)
            ->setType(SharedStripeConfig::ONBOARDING_TYPE);

        $this->merchantAppFacade->handleMerchantAppOnboardingStatusChanged($statusChangedTransfer);

        return $response->setIsSuccessful(true);
    }

    protected function resolveOnboardingStatus(Account $account): string
    {
        // Use ArrayAccess on StripeObject — (array) cast does not work for nested StripeObjects
        $requirementsObject = $account['requirements'] ?? null;

        /** @var array<string> $currentlyDue */
        $currentlyDue = $requirementsObject ? (array)($requirementsObject['currently_due'] ?? []) : [];

        /** @var array<string> $pastDue */
        $pastDue = $requirementsObject ? (array)($requirementsObject['past_due'] ?? []) : [];

        /** @var array<string> $eventuallyDue */
        $eventuallyDue = $requirementsObject ? (array)($requirementsObject['eventually_due'] ?? []) : [];

        $disabledReason = (string)($requirementsObject ? ($requirementsObject['disabled_reason'] ?? '') : '');

        if ($disabledReason !== '' && $disabledReason !== '0' && str_contains($disabledReason, 'rejected')) {
            $this->getLogger()->info(MessageBuilder::accountRejected(), ['disabledReason' => $disabledReason]);

            return MerchantAppOnboardingStatusInterface::REJECTED;
        }

        if ($account->charges_enabled && $account->payouts_enabled) {
            /** @var array<string> $pendingVerification */
            $pendingVerification = $requirementsObject ? (array)($requirementsObject['pending_verification'] ?? []) : [];
            if (count($pendingVerification) > 0) {
                return MerchantAppOnboardingStatusInterface::PENDING;
            }

            $transferCapability = $account['capabilities'] ? ($account['capabilities']['transfers'] ?? null) : null;

            if (!$disabledReason && count($currentlyDue) === 0) {
                if ($transferCapability !== 'active') {
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
