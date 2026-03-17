<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeMerchantPayoutTransfer;
use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;

interface StripeRepositoryInterface
{
    public function findPaymentByOrderReference(string $orderReference): ?StripePaymentTransfer;

    public function findPaymentByTransactionId(string $transactionId): ?StripePaymentTransfer;

    public function findMerchantByReference(string $merchantReference): ?StripeMerchantTransfer;

    /**
     * Returns the most recent successful forward transfer record for the given order+merchant.
     * Used by the reversal flow to retrieve the Stripe transferId needed for createReversal().
     */
    public function findSuccessfulMerchantPayoutByOrderReferenceAndMerchantReference(
        string $orderReference,
        string $merchantReference,
    ): ?StripeMerchantPayoutTransfer;

    /**
     * Returns the most recent successful reversal record for the given order+merchant.
     * Used by the OMS condition to determine whether the reverse-payout step succeeded.
     */
    public function findSuccessfulMerchantPayoutReversalByOrderReferenceAndMerchantReference(
        string $orderReference,
        string $merchantReference,
    ): ?StripeMerchantPayoutTransfer;
}
