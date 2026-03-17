<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Merchant\Calculator;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\SalesPaymentMerchantExtension\Communication\Dependency\Plugin\MerchantPayoutCalculatorPluginInterface;

/**
 * Default reverse payout calculator — used when no commission-aware plugin is registered.
 * Uses canceledAmount for partial refunds, falls back to sumPriceToPayAggregation for full reversals.
 */
class StripeMerchantPayoutReverseAmountCalculatorFallback implements MerchantPayoutCalculatorPluginInterface
{
    public function calculatePayoutAmount(ItemTransfer $itemTransfer, OrderTransfer $orderTransfer): int
    {
        return $itemTransfer->getCanceledAmount() ?? $itemTransfer->getSumPriceToPayAggregationOrFail();
    }
}
