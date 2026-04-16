<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\OrderTransfer;

interface PaymentRefunderInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems
     */
    public function refundPayment(OrderTransfer $orderTransfer, array $orderItems, int $refundAmount = 0): void;
}
