<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeTransmissionRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfers;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentFundsTransfer
{
    use LoggerTrait;

    public function __construct(
        protected StripeTransfers $stripeTransfers,
        protected PaymentReader $paymentReader,
        protected StripeRepositoryInterface $repository,
    ) {
    }

    /**
     * Transfers funds to the merchant's Stripe connected account.
     *
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     * @param string $merchantReference
     * @param int $amount Amount in minor units (cents)
     */
    public function transfer(OrderTransfer $orderTransfer, string $merchantReference, int $amount): void
    {
        $orderReference = $orderTransfer->getOrderReferenceOrFail();

        $payment = $this->paymentReader->getPaymentByOrderReference($orderReference);
        if ($payment === null || $payment->getLatestChargeId() === null) {
            $this->getLogger()->warning('Cannot transfer funds: no payment or charge ID found', [
                'orderReference' => $orderReference,
            ]);

            return;
        }

        $merchant = $this->repository->findMerchantByReference($merchantReference);
        if ($merchant === null || $merchant->getStripeAccountId() === null) {
            $this->getLogger()->warning('Cannot transfer funds: merchant has no Stripe account', [
                'orderReference' => $orderReference,
                'merchantReference' => $merchantReference,
            ]);

            return;
        }

        $request = (new StripeTransmissionRequestTransfer())
            ->setAmount((string)$amount)
            ->setCurrency((new CurrencyTransfer())->setCode($payment->getCurrencyCodeOrFail()))
            ->setDestination($merchant->getStripeAccountIdOrFail())
            ->setDescription('Transfer for order ' . $orderReference)
            ->setSourceTransaction($payment->getLatestChargeIdOrFail())
            ->setTransferGroup($orderReference)
            ->setMetadata([StripeConfig::METADATA_ORDER_REFERENCE => $orderReference]);

        $response = $this->stripeTransfers->transfer($request);

        if (!$response->getIsSuccessful()) {
            $this->getLogger()->error('Fund transfer to merchant failed', [
                'orderReference' => $orderReference,
                'merchantReference' => $merchantReference,
                'message' => $response->getMessage(),
            ]);
        }
    }
}
