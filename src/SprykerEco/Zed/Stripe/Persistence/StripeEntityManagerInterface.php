<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripePaymentTransfer;

interface StripeEntityManagerInterface
{
    public function createPayment(StripePaymentTransfer $stripePaymentTransfer): StripePaymentTransfer;

    public function updateTransactionId(string $orderReference, string $transactionId): void;

    public function saveMerchantStripeAccountId(string $merchantReference, string $stripeAccountId): void;
}
