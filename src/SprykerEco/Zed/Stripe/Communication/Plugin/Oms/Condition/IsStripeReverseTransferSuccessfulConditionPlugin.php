<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Condition;

use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Oms\Dependency\Plugin\Condition\ConditionInterface;

/**
 * Returns true when a successful Stripe Connect transfer reversal record exists for this order item's merchant.
 * Used by the StripeMerchantPayoutReverse01 subprocess to determine whether the reversal step succeeded.
 *
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class IsStripeReverseTransferSuccessfulConditionPlugin extends AbstractPlugin implements ConditionInterface
{
    /**
     * {@inheritDoc}
     * - Returns true when a successful reversal record exists in spy_stripe_merchant_payout
     *   for this order item's order reference and merchant reference.
     * - Returns true for non-marketplace items (no merchant reference) to let the OMS flow continue.
     *
     * @api
     */
    public function check(SpySalesOrderItem $orderItem): bool
    {
        $merchantReference = $orderItem->getMerchantReference();

        // Non-marketplace items skip payout reversal — let the OMS flow continue
        if ($merchantReference === null) {
            return true;
        }

        $orderReference = $orderItem->getOrder()->getOrderReference();

        return $this->getFacade()->isReverseTransferSuccessful($orderReference, $merchantReference);
    }
}
