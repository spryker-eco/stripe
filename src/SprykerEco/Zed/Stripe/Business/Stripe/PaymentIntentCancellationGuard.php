<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use SprykerEco\Shared\Stripe\StripeConfig;
use Stripe\PaymentIntent;

class PaymentIntentCancellationGuard implements PaymentIntentCancellationGuardInterface
{
    protected const string PAYMENT_METHOD_TYPE_US_BANK_ACCOUNT = 'us_bank_account';

    public function __construct(protected StripeConfig $stripeConfig)
    {
    }

    public function canBeCanceled(PaymentIntent $paymentIntent): bool
    {
        if (in_array($paymentIntent->status, $this->stripeConfig->getPaymentIntentNonCancellableStatuses(), true)) {
            return false;
        }

        if (in_array($paymentIntent->status, $this->stripeConfig->getPaymentIntentCancellableStatuses(), true)) {
            return true;
        }

        // ACH (us_bank_account) PaymentIntents in processing state can still be canceled
        return $paymentIntent->status === StripeConfig::PAYMENT_STATUS_PROCESSING
            && $this->isUsBankAccountPaymentMethod($paymentIntent);
    }

    protected function isUsBankAccountPaymentMethod(PaymentIntent $paymentIntent): bool
    {
        if (!$paymentIntent->__isset('payment_method')) {
            return false;
        }

        $paymentMethod = $paymentIntent->payment_method;

        return is_object($paymentMethod) && $paymentMethod->type === static::PAYMENT_METHOD_TYPE_US_BANK_ACCOUNT;
    }
}
