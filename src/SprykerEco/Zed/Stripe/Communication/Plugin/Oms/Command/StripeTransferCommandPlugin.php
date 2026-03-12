<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Command;

use Generated\Shared\Transfer\OrderTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandByOrderInterface;

/**
 * Transfers captured funds to each merchant's Stripe connected account (marketplace only).
 * Groups order items by merchant reference and issues one transfer per merchant.
 *
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeTransferCommandPlugin extends AbstractPlugin implements CommandByOrderInterface
{
    /**
     * {@inheritDoc}
     * - Groups order items by merchantReference.
     * - For each merchant group, calls StripeFacade::transferFunds() with the summed amount.
     * - Requires spy_stripe_merchant.stripe_account_id to be set for each merchant.
     *
     * @api
     *
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems

     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function run(array $orderItems, SpySalesOrder $orderEntity, ReadOnlyArrayObject $data): array
    {
        $orderTransfer = (new OrderTransfer())->setOrderReference($orderEntity->getOrderReference());

        $amountsByMerchant = $this->groupAmountsByMerchant($orderItems);

        foreach ($amountsByMerchant as $merchantReference => $amount) {
            $this->getFacade()->transferFunds($orderTransfer, $merchantReference, $amount);
        }

        return $orderItems;
    }

    /**
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return array<string, int> merchantReference → total amount in minor units
     */
    protected function groupAmountsByMerchant(array $orderItems): array
    {
        $amountsByMerchant = [];

        foreach ($orderItems as $orderItem) {
            $merchantReference = $orderItem->getMerchantReference();
            if ($merchantReference === null) {
                continue;
            }

            $amountsByMerchant[$merchantReference] = ($amountsByMerchant[$merchantReference] ?? 0)
                + (int)$orderItem->getPriceToPayAggregation();
        }

        return $amountsByMerchant;
    }
}
