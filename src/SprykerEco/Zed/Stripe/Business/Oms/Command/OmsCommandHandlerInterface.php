<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Oms\Command;

use Generated\Shared\Transfer\OrderTransfer;

interface OmsCommandHandlerInterface
{
    public function authorize(OrderTransfer $orderTransfer): void;

    public function capture(OrderTransfer $orderTransfer, int $captureAmount = 0): void;

    public function cancel(OrderTransfer $orderTransfer): void;

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems
     */
    public function refund(OrderTransfer $orderTransfer, array $orderItems, int $refundAmount = 0): void;
}
