<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\StripePaymentTransfer;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;

class PaymentReader
{
    public function __construct(protected StripeRepositoryInterface $repository)
    {
    }

    public function getPaymentByOrderReference(string $orderReference): ?StripePaymentTransfer
    {
        return $this->repository->findPaymentByOrderReference($orderReference);
    }

    public function getPaymentByTransactionId(string $transactionId): ?StripePaymentTransfer
    {
        return $this->repository->findPaymentByTransactionId($transactionId);
    }
}
