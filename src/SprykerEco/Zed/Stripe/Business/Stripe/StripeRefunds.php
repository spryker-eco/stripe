<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\StripeRefundRequestTransfer;
use Generated\Shared\Transfer\StripeRefundResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use Stripe\Exception\ApiErrorException;
use Stripe\Refund;

class StripeRefunds implements StripeRefundsInterface
{
    use LoggerTrait;

    public function __construct(protected StripeClientFactory $stripeClientFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(StripeRefundRequestTransfer $stripeRefundRequestTransfer): StripeRefundResponseTransfer
    {
        $stripeRefundResponseTransfer = new StripeRefundResponseTransfer();
        $stripeRefundResponseTransfer->setIsSuccessful(false)
            ->setStatus(Refund::STATUS_FAILED);

        try {
            $stripeClient = $this->stripeClientFactory->create();

            $paymentRefundParams = $this->createPaymentRefundParams($stripeRefundRequestTransfer);
            $stripeRefund = $stripeClient->refunds->create($paymentRefundParams);

            $stripeRefundResponseTransfer = $this->mapStripeRefundToResponseTransfer(
                $stripeRefund,
                $stripeRefundResponseTransfer,
            );
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error($apiErrorException, [
                StripeRefundRequestTransfer::TRANSACTION_ID => $stripeRefundRequestTransfer->getTransactionId(),
                StripeRefundRequestTransfer::AMOUNT => $stripeRefundRequestTransfer->getAmount(),
                StripeRefundRequestTransfer::ORDER_ITEM_SKUS => $stripeRefundRequestTransfer->getOrderItemSkus(),
            ]);

            $stripeRefundResponseTransfer->setMessage($apiErrorException->getMessage());
        }

        return $stripeRefundResponseTransfer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function createPaymentRefundParams(StripeRefundRequestTransfer $stripeRefundRequestTransfer): array
    {
        return [
            'payment_intent' => $stripeRefundRequestTransfer->getTransactionIdOrFail(),
            'amount' => abs($stripeRefundRequestTransfer->getAmountOrFail()),
            'metadata' => [
                'order_item_skus' => json_encode($stripeRefundRequestTransfer->getOrderItemSkus()),
            ],
            'reason' => Refund::REASON_REQUESTED_BY_CUSTOMER,
        ];
    }

    protected function mapStripeRefundToResponseTransfer(
        Refund $stripeRefund,
        StripeRefundResponseTransfer $stripeRefundResponseTransfer,
    ): StripeRefundResponseTransfer {
        if (!$stripeRefund->__isset('id')) {
            return $stripeRefundResponseTransfer->setIsSuccessful(false)
                ->setMessage('Payment Refund creation failed: ID is missing in the response.');
        }

        if (!$stripeRefund->__isset('status') || $stripeRefund->status === null) {
            return $stripeRefundResponseTransfer->setIsSuccessful(false)
                ->setMessage('Payment Refund creation failed: status is missing in the response.');
        }

        $stripeRefundResponseTransfer->setIsSuccessful(true)
            ->setStatus($stripeRefund->status)
            ->setRefundId($stripeRefund->id);

        if ($stripeRefund->status === Refund::STATUS_FAILED) {
            return $stripeRefundResponseTransfer->setMessage(
                $stripeRefund->__isset('failure_reason')
                    ? $stripeRefund->failure_reason
                    : sprintf('Refund failed with status "%s"', $stripeRefund->status),
            );
        }

        if ($stripeRefund->status !== Refund::STATUS_SUCCEEDED) {
            return $stripeRefundResponseTransfer->setMessage(
                $stripeRefund->__isset('failure_reason')
                    ? $stripeRefund->failure_reason
                    : sprintf('Refund failed with status "%s"', $stripeRefund->status),
            );
        }

        return $stripeRefundResponseTransfer;
    }
}
