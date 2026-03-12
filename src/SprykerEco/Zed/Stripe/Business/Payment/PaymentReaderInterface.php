<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\StripePaymentTransfer;

interface PaymentReaderInterface
{
    public function getPaymentByOrderReference(string $orderReference): ?StripePaymentTransfer;

    public function getPaymentByTransactionId(string $transactionId): ?StripePaymentTransfer;
}
