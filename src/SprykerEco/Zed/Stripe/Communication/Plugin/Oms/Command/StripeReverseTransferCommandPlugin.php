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
 * Reverses previously made Stripe Connect transfers to merchants (marketplace only).
 * Fetches the full OrderTransfer (with commission data) and passes ItemTransfer[] per merchant
 * to the facade so that the configured reverse payout calculator plugin can deduce commission refunds.
 *
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeReverseTransferCommandPlugin extends AbstractPlugin implements CommandByOrderInterface
{
    /**
     * {@inheritDoc}
     * - Fetches the full OrderTransfer (including commission fields) via Sales facade.
     * - Groups matching ItemTransfers by merchantReference.
     * - For each merchant group, calls StripeFacade::reverseFunds() so the configured
     *   MerchantPayoutReverseCalculatorPlugin can calculate commission-adjusted reversal amounts.
     * - Reads the previous transfer ID from spy_stripe_merchant_payout to pass to createReversal().
     *
     * @api
     *
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function run(array $orderItems, SpySalesOrder $orderEntity, ReadOnlyArrayObject $data): array
    {
        $orderTransfer = $this->getFactory()
            ->getSalesFacade()
            ->findOrderByIdSalesOrder($orderEntity->getIdSalesOrder());

        if ($orderTransfer === null) {
            return $orderItems;
        }

        $itemTransfersByMerchant = $this->groupItemTransfersByMerchant($orderItems, $orderTransfer);

        foreach ($itemTransfersByMerchant as $merchantReference => $merchantItemTransfers) {
            $this->getFacade()->reverseFunds($orderTransfer, $merchantReference, $merchantItemTransfers);
        }

        return $orderItems;
    }

    /**
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItemEntities
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return array<string, array<\Generated\Shared\Transfer\ItemTransfer>>
     */
    protected function groupItemTransfersByMerchant(array $orderItemEntities, OrderTransfer $orderTransfer): array
    {
        $itemIdToEntityMerchantMap = $this->buildItemIdToMerchantMap($orderItemEntities);

        $result = [];

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $merchantReference = $itemIdToEntityMerchantMap[$itemTransfer->getIdSalesOrderItem()] ?? null;
            if ($merchantReference === null) {
                continue;
            }

            $result[$merchantReference][] = $itemTransfer;
        }

        return $result;
    }

    /**
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItemEntities
     *
     * @return array<int, string> Maps idSalesOrderItem → merchantReference
     */
    protected function buildItemIdToMerchantMap(array $orderItemEntities): array
    {
        $map = [];

        foreach ($orderItemEntities as $entity) {
            $merchantReference = $entity->getMerchantReference();
            if ($merchantReference === null) {
                continue;
            }

            $map[$entity->getIdSalesOrderItem()] = $merchantReference;
        }

        return $map;
    }
}
