<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;

interface PaymentFundsReverseTransferInterface
{
    /**
     * Reverses a previously made forward transfer to the merchant's Stripe connected account.
     * Reads the Stripe transferId from the persisted payout record and calls createReversal().
     * Calculates the reversal amount per item using the configured reverse payout calculator plugin.
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems Items belonging to this merchant for this OMS transition.
     */
    public function reverseTransfer(OrderTransfer $orderTransfer, string $merchantReference, array $orderItems): void;
}
