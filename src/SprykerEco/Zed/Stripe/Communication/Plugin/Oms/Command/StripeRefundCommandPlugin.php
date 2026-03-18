<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Communication\Plugin\Oms\Command;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandByOrderInterface;

/**
 * @method \SprykerEco\Zed\Stripe\Business\StripeFacadeInterface getFacade()
 * @method \SprykerEco\Zed\Stripe\Communication\StripeCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\Stripe\StripeConfig getConfig()
 */
class StripeRefundCommandPlugin extends AbstractPlugin implements CommandByOrderInterface
{
    /**
     * {@inheritDoc}
     * - Initiates a Stripe Refund for the given order items.
     * - Refund amount is calculated via RefundFacade: sum of item refundable amounts + shipping on the last refund.
     * - Status transition is driven by the `charge.refund.updated` webhook.
     *
     * @api
     *
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems

     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function run(array $orderItems, SpySalesOrder $orderEntity, ReadOnlyArrayObject $data): array
    {
        $refundTransfer = $this->getFactory()->getRefundFacade()->calculateRefund($orderItems, $orderEntity);

        $orderTransfer = (new OrderTransfer())->setOrderReference($orderEntity->getOrderReference());

        $itemTransfers = array_map(
            static fn ($orderItem): ItemTransfer => (new ItemTransfer())
                ->setSku($orderItem->getSku()),
            $orderItems,
        );

        $this->getFacade()->refundPayment($orderTransfer, $itemTransfers, $refundTransfer->getAmountOrFail());

        return $orderItems;
    }
}
