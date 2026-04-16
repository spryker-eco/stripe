<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransmissionResponseCollectionTransfer;

interface PayoutTransmissionExecutorInterface
{
    /**
     * Executes payment transmission for prepared PaymentTransmissionItemTransfers via Stripe API.
     * Groups items by merchant reference, creates Stripe transfers (positive amounts)
     * or transfer reversals (negative amounts with transferId).
     * Returns responses for SalesPaymentMerchant to persist to spy_sales_payment_merchant_payout.
     *
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $paymentTransmissionItemTransfers
     */
    public function executePayoutTransmission(
        array $paymentTransmissionItemTransfers,
        OrderTransfer $orderTransfer,
    ): PaymentTransmissionResponseCollectionTransfer;
}
