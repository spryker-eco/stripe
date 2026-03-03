<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeTransfer;

interface StripeEntityManagerInterface
{
    public function savePayment(StripeTransfer $stripeTransfer): StripeTransfer;

    public function updatePaymentStatus(
        int $idStripe,
        string $status,
        ?string $providerReference = null,
    ): void;

    public function savePaymentOrderItem(
        int $idStripe,
        int $idSalesOrderItem,
        string $status,
    ): void;

    public function saveNotification(string $payload): void;
}
