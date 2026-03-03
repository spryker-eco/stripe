<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeTransfer;

interface StripeRepositoryInterface
{
    public function findStripeByIdSalesOrder(int $idSalesOrder): ?StripeTransfer;

    public function findStripeByProviderReference(string $providerReference): ?StripeTransfer;
}
