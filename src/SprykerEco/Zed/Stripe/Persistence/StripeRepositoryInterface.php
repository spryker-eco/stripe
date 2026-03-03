<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Persistence;

use Generated\Shared\Transfer\StripeMerchantTransfer;
use Generated\Shared\Transfer\StripePaymentTransfer;

interface StripeRepositoryInterface
{
    public function findPaymentByOrderReference(string $orderReference): ?StripePaymentTransfer;

    public function findPaymentByTransactionId(string $transactionId): ?StripePaymentTransfer;

    public function findMerchantByReference(string $merchantReference): ?StripeMerchantTransfer;
}
