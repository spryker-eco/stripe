<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;

interface PaymentFundsTransferInterface
{
    /**
     * Transfers captured funds to the merchant's Stripe connected account.
     * Calculates the payout amount per item using the configured payout calculator plugin.
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems Items belonging to this merchant for this OMS transition.
     */
    public function transfer(OrderTransfer $orderTransfer, string $merchantReference, array $orderItems): void;
}
