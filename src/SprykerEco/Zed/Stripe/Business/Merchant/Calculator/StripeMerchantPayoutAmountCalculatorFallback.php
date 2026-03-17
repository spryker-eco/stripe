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
 * Default payout calculator — used when no commission-aware plugin is registered.
 * Returns the full item price without any commission deduction.
 */
class StripeMerchantPayoutAmountCalculatorFallback implements MerchantPayoutCalculatorPluginInterface
{
    public function calculatePayoutAmount(ItemTransfer $itemTransfer, OrderTransfer $orderTransfer): int
    {
        return $itemTransfer->getSumPriceToPayAggregationOrFail();
    }
}
