<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransmissionItemTransfer;
use Generated\Shared\Transfer\PaymentTransmissionResponseCollectionTransfer;
use Generated\Shared\Transfer\PaymentTransmissionResponseTransfer;
use Generated\Shared\Transfer\StripeIntentRequestTransfer;
use Generated\Shared\Transfer\StripeIntentResponseTransfer;
use Generated\Shared\Transfer\StripeTransmissionRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Message\MessageBuilder;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeIntentsInterface;
use SprykerEco\Zed\Stripe\Business\Stripe\StripeTransfersInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeRepositoryInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class PayoutTransmissionExecutor implements PayoutTransmissionExecutorInterface
{
    use LoggerTrait;

    public function __construct(
        protected StripeTransfersInterface $stripeTransfers,
        protected StripeIntentsInterface $stripeIntents,
        protected PaymentReaderInterface $paymentReader,
        protected StripeRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function executePayoutTransmission(
        array $paymentTransmissionItemTransfers,
        OrderTransfer $orderTransfer,
    ): PaymentTransmissionResponseCollectionTransfer {
        $responseCollection = new PaymentTransmissionResponseCollectionTransfer();
        $orderReference = $orderTransfer->getOrderReferenceOrFail();

        $intentResponse = $this->resolveIntentResponse($orderReference);

        if ($intentResponse === null) {
            return $responseCollection;
        }

        $groupedByMerchant = $this->groupItemsByMerchantReference($paymentTransmissionItemTransfers);

        foreach ($groupedByMerchant as $merchantReference => $merchantItems) {
            $response = $this->executeTransmissionForMerchant(
                $merchantItems,
                $orderReference,
                $merchantReference,
                $intentResponse->getLatestChargeIdOrFail(),
                $intentResponse->getCurrencyCodeOrFail(),
            );

            $responseCollection->addPaymentTransmission($response);
        }

        return $responseCollection;
    }

    /**
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $merchantItems
     */
    protected function executeTransmissionForMerchant(
        array $merchantItems,
        string $orderReference,
        string $merchantReference,
        string $chargeId,
        string $currencyCode,
    ): PaymentTransmissionResponseTransfer {
        $response = (new PaymentTransmissionResponseTransfer())
            ->setMerchantReference($merchantReference)
            ->setOrderReference($orderReference)
            ->setIsSuccessful(false);

        $merchant = $this->repository->findMerchantByReference($merchantReference);

        if ($merchant === null || $merchant->getStripeAccountId() === null) {
            $this->getLogger()->warning('Cannot execute transmission: merchant has no Stripe account', [
                'orderReference' => $orderReference,
                'merchantReference' => $merchantReference,
            ]);

            $response->setFailureMessage('Merchant has no Stripe account');

            return $response;
        }

        $totalAmount = $this->calculateTotalAmount($merchantItems);

        // Stripe requires amount >= 1 for transfers and <= -1 for reversals
        if ($totalAmount === 0) {
            $response->setIsSuccessful(true);
            $this->addPaymentTransmissionItemsToResponse($response, $merchantItems);

            return $response;
        }

        $isReversal = $totalAmount < 0;
        $transferId = $isReversal ? $this->resolveTransferIdForReversal($merchantItems) : null;

        $request = (new StripeTransmissionRequestTransfer())
            ->setAmount((string)$totalAmount)
            ->setCurrency((new CurrencyTransfer())->setCode($currencyCode))
            ->setDestination($merchant->getStripeAccountIdOrFail())
            ->setDescription(MessageBuilder::transmissionRequestDescription($orderReference, $merchantReference))
            ->setSourceTransaction($chargeId)
            ->setTransferGroup($orderReference)
            ->setTransferId($transferId)
            ->setMetadata($this->buildTransferMetadata($orderReference, $merchantReference, $merchantItems));

        $stripeResponse = $this->stripeTransfers->transfer($request);

        $response
            ->setIsSuccessful($stripeResponse->getIsSuccessful() === true)
            ->setTransferId($stripeResponse->getTransferId())
            ->setAmount((string)$totalAmount);

        $this->addPaymentTransmissionItemsToResponse($response, $merchantItems);

        if (!$stripeResponse->getIsSuccessful()) {
            $response->setFailureMessage($stripeResponse->getMessage());
            $this->getLogger()->error('Fund transmission failed', [
                'orderReference' => $orderReference,
                'merchantReference' => $merchantReference,
                'message' => $stripeResponse->getMessage(),
            ]);
        }

        return $response;
    }

    protected function resolveIntentResponse(string $orderReference): ?StripeIntentResponseTransfer
    {
        $payment = $this->paymentReader->getPaymentByOrderReference($orderReference);

        if ($payment === null || $payment->getTransactionId() === null) {
            $this->getLogger()->warning('Cannot execute transmission: no payment found', [
                'orderReference' => $orderReference,
            ]);

            return null;
        }

        $intentResponse = $this->stripeIntents->get(
            (new StripeIntentRequestTransfer())->setTransactionId($payment->getTransactionIdOrFail()),
        );

        if ($intentResponse->getLatestChargeId() === null) {
            $this->getLogger()->warning('Cannot execute transmission: PaymentIntent has no charge ID yet', [
                'orderReference' => $orderReference,
                'transactionId' => $payment->getTransactionId(),
            ]);

            return null;
        }

        return $intentResponse;
    }

    /**
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $paymentTransmissionItemTransfers
     *
     * @return array<string, list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer>>
     */
    protected function groupItemsByMerchantReference(array $paymentTransmissionItemTransfers): array
    {
        $grouped = [];

        foreach ($paymentTransmissionItemTransfers as $item) {
            $grouped[$item->getMerchantReferenceOrFail()][] = $item;
        }

        return $grouped;
    }

    /**
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $items
     */
    protected function calculateTotalAmount(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            $total += (int)$item->getAmountOrFail();
        }

        return $total;
    }

    /**
     * Resolves the Stripe transfer ID for reversal from the items' transferId.
     * The transferId on PaymentTransmissionItemTransfer is set by SalesPaymentMerchant's
     * PaymentTransmissionItemExpander from spy_sales_payment_merchant_payout,
     * which stores the Stripe transfer ID returned by the forward transmission.
     *
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $items
     */
    protected function resolveTransferIdForReversal(array $items): ?string
    {
        foreach ($items as $item) {
            if ($item->getTransferId() !== null && $item->getTransferId() !== '') {
                return $item->getTransferId();
            }
        }

        return null;
    }

    /**
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $items
     *
     * @return array<string, string>
     */
    protected function buildTransferMetadata(
        string $orderReference,
        string $merchantReference,
        array $items,
    ): array {
        $metadata = [
            StripeConfig::METADATA_ORDER_REFERENCE => $orderReference,
            StripeConfig::METADATA_MERCHANT_REFERENCE => $merchantReference,
        ];

        $type = $this->resolveTransmissionType($items);

        if ($type !== null) {
            $metadata['type'] = $type;
        }

        $itemReferences = $this->collectItemReferences($items);

        if ($itemReferences !== '') {
            $metadata['itemReferences'] = $itemReferences;
        }

        return $metadata;
    }

    /**
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $items
     */
    protected function resolveTransmissionType(array $items): ?string
    {
        foreach ($items as $item) {
            if ($item->getType() !== null) {
                return $item->getType();
            }
        }

        return null;
    }

    /**
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $items
     */
    protected function collectItemReferences(array $items): string
    {
        $references = [];

        foreach ($items as $item) {
            if ($item->getItemReference() !== null) {
                $references[] = $item->getItemReference();
            }
        }

        return implode(',', $references);
    }

    /**
     * @param list<\Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $merchantItems
     */
    protected function addPaymentTransmissionItemsToResponse(
        PaymentTransmissionResponseTransfer $response,
        array $merchantItems,
    ): void {
        foreach ($merchantItems as $item) {
            $response->addPaymentTransmissionItem(
                (new PaymentTransmissionItemTransfer())
                    ->setItemReference($item->getItemReferenceOrFail())
                    ->setMerchantReference($item->getMerchantReferenceOrFail())
                    ->setOrderReference($item->getOrderReferenceOrFail())
                    ->setAmount($item->getAmount())
                    ->setTransferId($response->getTransferId())
                    ->setIsSuccessful($response->getIsSuccessful()),
            );
        }
    }
}
