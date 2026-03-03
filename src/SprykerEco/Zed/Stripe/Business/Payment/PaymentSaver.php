<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Generated\Shared\Transfer\StripeTransfer;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentSaver implements PaymentSaverInterface
{
    public function __construct(
        protected StripeEntityManagerInterface $entityManager,
        protected StripeConfig $config,
    ) {
    }

    public function saveOrderPayment(QuoteTransfer $quoteTransfer, SaveOrderTransfer $saveOrderTransfer): void
    {
        $paymentTransfer = $quoteTransfer->getPayment();
        $stripeTransfer = $paymentTransfer->getStripe();

        if ($paymentTransfer->getPaymentProvider() !== SharedStripeConfig::PAYMENT_PROVIDER_NAME) {
            return;
        }

        $stripeTransfer = $this->createStripeTransfer(
            $stripeTransfer,
            $saveOrderTransfer,
        );

        $stripeTransfer = $this->entityManager->savePayment($stripeTransfer);

        $this->saveOrderItems($stripeTransfer, $saveOrderTransfer);
    }

    protected function createStripeTransfer(
        StripeTransfer $stripeTransfer,
        SaveOrderTransfer $saveOrderTransfer,
    ): StripeTransfer {
        $stripeTransfer
            ->setFkSalesOrder($saveOrderTransfer->getIdSalesOrderOrFail());

        return $stripeTransfer;
    }

    protected function saveOrderItems(
        StripeTransfer $stripeTransfer,
        SaveOrderTransfer $saveOrderTransfer,
    ): void {
        foreach ($saveOrderTransfer->getOrderItems() as $itemTransfer) {
            $this->entityManager->savePaymentOrderItem(
                $stripeTransfer->getIdStripeOrFail(),
                $itemTransfer->getIdSalesOrderItemOrFail(),
                $this->config::PAYMENT_STATUS_NEW,
            );
        }
    }
}
