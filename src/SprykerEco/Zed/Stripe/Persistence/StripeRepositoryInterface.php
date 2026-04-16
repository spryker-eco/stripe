<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
