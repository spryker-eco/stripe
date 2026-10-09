<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Checker;

use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;

class OrderOwnershipChecker implements OrderOwnershipCheckerInterface
{
    public function __construct(protected SalesFacadeInterface $salesFacade)
    {
    }

    public function isOrderOwnedByCustomer(string $orderReference, ?string $customerReference): bool
    {
        if ($customerReference === null || $customerReference === '') {
            return false;
        }

        $orderTransfer = $this->salesFacade->getCustomerOrderByOrderReference(
            (new OrderTransfer())
                ->setOrderReference($orderReference)
                ->setCustomerReference($customerReference),
        );

        return $orderTransfer->getIdSalesOrder() !== null;
    }
}
