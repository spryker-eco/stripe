<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeMerchantPayoutTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;

interface StripeEntityManagerInterface
{
    public function createPayment(StripePaymentTransfer $stripePaymentTransfer): StripePaymentTransfer;

    public function updateTransactionId(string $orderReference, string $transactionId): void;

    public function saveMerchantStripeAccountId(string $merchantReference, string $stripeAccountId): void;

    /**
     * Persists a forward transfer or reversal record for a given order+merchant.
     * Returns the transfer with idStripeMerchantPayout populated.
     */
    public function saveMerchantPayout(StripeMerchantPayoutTransfer $stripeMerchantPayoutTransfer): StripeMerchantPayoutTransfer;
}
