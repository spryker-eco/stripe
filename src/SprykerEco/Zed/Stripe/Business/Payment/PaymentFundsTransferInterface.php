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
     * @param int $amount Amount in minor units (cents).
     */
    public function transfer(OrderTransfer $orderTransfer, string $merchantReference, int $amount): void;
}
