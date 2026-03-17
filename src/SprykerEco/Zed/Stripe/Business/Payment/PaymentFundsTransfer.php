<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\StripeMerchantPayoutTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeTransmissionRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\SalesPaymentMerchantExtension\Communication\Dependency\Plugin\MerchantPayoutCalculatorPluginInterface;
use SprykerEco\Zed\Stripe\Business\Message\MessageBuilder;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfersInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentFundsTransfer implements PaymentFundsTransferInterface
{
    use LoggerTrait;

    public function __construct(
        protected StripeTransfersInterface $stripeTransfers,
        protected StripeIntentsInterface $stripeIntents,
        protected PaymentReaderInterface $paymentReader,
        protected StripeRepositoryInterface $repository,
        protected StripeEntityManagerInterface $entityManager,
        protected MerchantPayoutCalculatorPluginInterface $amountCalculator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transfer(OrderTransfer $orderTransfer, string $merchantReference, array $orderItems): void
    {
        $orderReference = $orderTransfer->getOrderReferenceOrFail();

        $payment = $this->paymentReader->getPaymentByOrderReference($orderReference);
        if ($payment === null || $payment->getTransactionId() === null) {
            $this->getLogger()->warning('Cannot transfer funds: no payment found', [
                'orderReference' => $orderReference,
            ]);

            return;
        }

        $intentResponse = $this->stripeIntents->get(
            (new StripeIntentRequestTransfer())->setTransactionId($payment->getTransactionIdOrFail()),
        );

        if ($intentResponse->getLatestChargeId() === null) {
            $this->getLogger()->warning('Cannot transfer funds: PaymentIntent has no charge ID yet', [
                'orderReference' => $orderReference,
                'transactionId' => $payment->getTransactionId(),
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

        $amount = $this->calculateTotalPayoutAmount($orderItems, $orderTransfer);

        $request = (new StripeTransmissionRequestTransfer())
            ->setAmount((string)$amount)
            ->setCurrency((new CurrencyTransfer())->setCode($intentResponse->getCurrencyCodeOrFail()))
            ->setDestination($merchant->getStripeAccountIdOrFail())
            ->setDescription(MessageBuilder::transmissionRequestDescription($orderReference, $merchantReference))
            ->setSourceTransaction($intentResponse->getLatestChargeIdOrFail())
            ->setTransferGroup($orderReference)
            ->setMetadata([
                StripeConfig::METADATA_ORDER_REFERENCE => $orderReference,
                StripeConfig::METADATA_MERCHANT_REFERENCE => $merchantReference,
            ]);

        $response = $this->stripeTransfers->transfer($request);

        if (!$response->getIsSuccessful()) {
            $this->getLogger()->error('Fund transfer to merchant failed', [
                'orderReference' => $orderReference,
                'merchantReference' => $merchantReference,
                'message' => $response->getMessage(),
            ]);
        }

        $this->entityManager->saveMerchantPayout(
            (new StripeMerchantPayoutTransfer())
                ->setOrderReference($orderReference)
                ->setMerchantReference($merchantReference)
                ->setAmount($amount)
                ->setTransferId($response->getTransferId())
                ->setIsSuccessful($response->getIsSuccessful() === true)
                ->setIsReversed(false),
        );
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $orderItems
     */
    protected function calculateTotalPayoutAmount(array $orderItems, OrderTransfer $orderTransfer): int
    {
        $total = 0;

        foreach ($orderItems as $itemTransfer) {
            $total += $this->amountCalculator->calculatePayoutAmount($itemTransfer, $orderTransfer);
        }

        return $total;
    }
}
