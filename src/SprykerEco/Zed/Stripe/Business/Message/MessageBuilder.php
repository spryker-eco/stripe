<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Message;

class MessageBuilder
{
    public static function transferCapabilitiesIsNotActive(): string
    {
        return 'Transfers capability is not active.';
    }

    public static function chargesAreDisabled(): string
    {
        return 'Charges are disabled.';
    }

    public static function payoutsAreDisabled(): string
    {
        return 'Payouts are disabled.';
    }

    public static function requirementsCurrentlyDue(): string
    {
        return 'There are requirements that are currently due.';
    }

    public static function requirementsEventuallyDue(): string
    {
        return 'There are requirements that are eventually due (Can cause a requires action when not handled in time).';
    }

    public static function requirementsPastDue(): string
    {
        return 'There are requirements that are past due.';
    }

    public static function accountRejected(): string
    {
        return 'The account was rejected.';
    }

    public static function accountPending(): string
    {
        return 'The account has a pending state.';
    }

    public static function paymentIntentDoesNotHaveALatestChargeId(): string
    {
        return 'The PaymentIntent does not have a latest charge id';
    }

    public static function transferReversalDoesNotHaveAPreviousMadeTransfer(): string
    {
        return 'Can not reverse a transfer that does not have a previous made transfer.';
    }

    /**
     * @param array<string> $validStatesForTransfer
     */
    public static function merchantHasNotAOnboardingStateWhereWeCanMakeTransfersOrReverseTransfers(
        string $onboardingStatus,
        array $validStatesForTransfer,
    ): string {
        return sprintf(
            'The Merchant has not an onboarding status where we can make a transfer or reverse transfers. The onboarding status of the Merchant is "%s". Valid states are: %s',
            $onboardingStatus,
            implode(', ', $validStatesForTransfer),
        );
    }

    public static function transmissionRequestDescription(string $orderReference, string $merchantReference): string
    {
        return sprintf('Transfer for order %s to merchant %s', $orderReference, $merchantReference);
    }

    /**
     * @param array<string> $supportedEventNames
     */
    public static function webhookRequestEventNotSupported(string $webhookType, string $eventName, array $supportedEventNames): string
    {
        return sprintf(
            'Webhook request event "%s" not supported in the webhook type "%s". Supported events are: %s',
            $eventName,
            $webhookType,
            implode(', ', $supportedEventNames),
        );
    }
}
