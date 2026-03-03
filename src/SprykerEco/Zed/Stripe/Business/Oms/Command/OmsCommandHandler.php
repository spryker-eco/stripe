<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Oms\Command;

use Exception;
use Generated\Shared\Transfer\StripeApiErrorResponseTransfer;
use Generated\Shared\Transfer\StripeAuthorizeRequestTransfer;
use Generated\Shared\Transfer\StripeAuthorizeResponseTransfer;
use Generated\Shared\Transfer\StripeCancelRequestTransfer;
use Generated\Shared\Transfer\StripeCancelResponseTransfer;
use Generated\Shared\Transfer\StripeCaptureRequestTransfer;
use Generated\Shared\Transfer\StripeCaptureResponseTransfer;
use Generated\Shared\Transfer\StripeTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use SprykerEco\Client\Stripe\StripeClientInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentReaderInterface;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class OmsCommandHandler implements OmsCommandHandlerInterface
{
    public function __construct(
        protected StripeClientInterface $client,
        protected PaymentReaderInterface $paymentReader,
        protected StripeEntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeAuthorizeCommand(SpySalesOrder $orderEntity, array $orderItems): void
    {
        $stripeTransfer = $this->paymentReader->findPaymentByIdSalesOrder(
            $orderEntity->getIdSalesOrder(),
        );

        if ($stripeTransfer === null) {
            return;
        }

        $stripeAuthorizeRequestTransfer = $this->buildAuthorizeRequest($stripeTransfer);

        try {
            $stripeAuthorizeResponseTransfer = $this->client->authorize($stripeAuthorizeRequestTransfer);

            if (!$stripeAuthorizeResponseTransfer->getIsSuccess()) {
                $this->handleAuthorizeError($stripeTransfer, $stripeAuthorizeRequestTransfer, $stripeAuthorizeResponseTransfer->getErrorResponse());

                return;
            }

            $this->updatePaymentAfterAuthorize($stripeTransfer, $stripeAuthorizeRequestTransfer, $stripeAuthorizeResponseTransfer);
        } catch (Exception $exception) {
            $this->handleAuthorizeError($stripeTransfer, $stripeAuthorizeRequestTransfer, null);
        }
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeCaptureCommand(SpySalesOrder $orderEntity, array $orderItems): void
    {
        $stripeTransfer = $this->paymentReader->findPaymentByIdSalesOrder(
            $orderEntity->getIdSalesOrder(),
        );

        if ($stripeTransfer === null) {
            return;
        }

        $stripeCaptureRequestTransfer = $this->buildCaptureRequest($stripeTransfer);

        try {
            $stripeCaptureResponseTransfer = $this->client->capture($stripeCaptureRequestTransfer);

            if (!$stripeCaptureResponseTransfer->getIsSuccess()) {
                $this->handleCaptureError($stripeTransfer, $stripeCaptureRequestTransfer, $stripeCaptureResponseTransfer->getErrorResponse());

                return;
            }

            $this->updatePaymentAfterCapture($stripeTransfer, $stripeCaptureRequestTransfer, $stripeCaptureResponseTransfer);
        } catch (Exception $exception) {
            $this->handleCaptureError($stripeTransfer, $stripeCaptureRequestTransfer, null);
        }
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     *
     * @return void
     */
    public function executeCancelCommand(SpySalesOrder $orderEntity, array $orderItems): void
    {
        $stripeTransfer = $this->paymentReader->findPaymentByIdSalesOrder(
            $orderEntity->getIdSalesOrder(),
        );

        if ($stripeTransfer === null) {
            return;
        }

        $stripeCancelRequestTransfer = $this->buildCancelRequest($stripeTransfer);

        try {
            $stripeCancelResponseTransfer = $this->client->cancel($stripeCancelRequestTransfer);

            if (!$stripeCancelResponseTransfer->getIsSuccess()) {
                $this->handleCancelError($stripeTransfer, $stripeCancelRequestTransfer, $stripeCancelResponseTransfer->getErrorResponse());

                return;
            }

            $this->updatePaymentAfterCancel($stripeTransfer, $stripeCancelRequestTransfer, $stripeCancelResponseTransfer);
        } catch (Exception $exception) {
            $this->handleCancelError($stripeTransfer, $stripeCancelRequestTransfer, null);
        }
    }

    protected function buildAuthorizeRequest(StripeTransfer $stripeTransfer): StripeAuthorizeRequestTransfer
    {
        // TODO: Compose the request transfer from data in the StripeTransfer.
        // If your payment service provider requires additional data not available in StripeTransfer,
        // add additional parameters to this method signature and pass them from executeAuthorizeCommand.
        // e.g.
        // return (new StripeAuthorizeRequestTransfer())
        //     ->setAmount($stripeTransfer->getAmount())
        //     ->setCurrency($stripeTransfer->getCurrency())
        //     ->setPaymentMethodToken($stripeTransfer->getPaymentMethodToken())
        //     ->setOrderReference($stripeTransfer->getOrderReference());
        return (new StripeAuthorizeRequestTransfer());
    }

    protected function handleAuthorizeError(
        StripeTransfer $stripeTransfer,
        StripeAuthorizeRequestTransfer $stripeAuthorizeRequestTransfer,
        ?StripeApiErrorResponseTransfer $stripeApiErrorResponseTransfer,
    ): void {
        // TODO: Define status constants in StripeConfig based on payment service provider specific statuses.
        // Extract error details from $stripeApiErrorResponseTransfer if needed and pass appropriate status to updatePaymentStatus.
        // You may need to handle different error types with different status constants.
        // e.g.
        // $this->entityManager->updatePaymentStatus(
        //     $stripeTransfer->getIdStripeOrFail(),
        //     StripeConfig::PAYMENT_STATUS_AUTHORIZATION_FAILED,
        //     $stripeApiErrorResponseTransfer?->getProviderReference(),
        // );
        $this->entityManager->updatePaymentStatus(
            $stripeTransfer->getIdStripeOrFail(),
            StripeConfig::PAYMENT_STATUS_AUTHORIZATION_FAILED,
        );
    }

    protected function updatePaymentAfterAuthorize(
        StripeTransfer $stripeTransfer,
        StripeAuthorizeRequestTransfer $stripeAuthorizeRequestTransfer,
        StripeAuthorizeResponseTransfer $stripeAuthorizeResponseTransfer,
    ): void {
        // TODO: Define status constants in StripeConfig based on payment service provider specific statuses.
        // Extract the appropriate status from $stripeAuthorizeResponseTransfer and pass it to updatePaymentStatus.
        // e.g.
        // $this->entityManager->updatePaymentStatus(
        //     $stripeTransfer->getIdStripeOrFail(),
        //     StripeConfig::PAYMENT_STATUS_AUTHORIZED,
        //     $stripeAuthorizeResponseTransfer->getProviderReference(),
        // );
        $this->entityManager->updatePaymentStatus(
            $stripeTransfer->getIdStripeOrFail(),
            '',
        );
    }

    protected function buildCaptureRequest(StripeTransfer $stripeTransfer): StripeCaptureRequestTransfer
    {
        // TODO: Compose the request transfer from data in the StripeTransfer.
        // If your payment service provider requires additional data not available in StripeTransfer,
        // add additional parameters to this method signature and pass them from executeCaptureCommand.
        // e.g.
        // return (new StripeCaptureRequestTransfer())
        //     ->setProviderReference($stripeTransfer->getProviderReference())
        //     ->setAmount($stripeTransfer->getAmount())
        //     ->setCurrency($stripeTransfer->getCurrency());
        return (new StripeCaptureRequestTransfer());
    }

    protected function handleCaptureError(
        StripeTransfer $stripeTransfer,
        StripeCaptureRequestTransfer $stripeCaptureRequestTransfer,
        ?StripeApiErrorResponseTransfer $stripeApiErrorResponseTransfer
    ): void {
        // TODO: Define status constants in StripeConfig based on payment service provider specific statuses.
        // Extract error details from $stripeApiErrorResponseTransfer if needed and pass appropriate status to updatePaymentStatus.
        // You may need to handle different error types with different status constants.
        // e.g.
        // $this->entityManager->updatePaymentStatus(
        //     $stripeTransfer->getIdStripeOrFail(),
        //     StripeConfig::PAYMENT_STATUS_CAPTURE_FAILED,
        //     $stripeApiErrorResponseTransfer?->getProviderReference(),
        // );
        $this->entityManager->updatePaymentStatus(
            $stripeTransfer->getIdStripeOrFail(),
            StripeConfig::PAYMENT_STATUS_CAPTURE_FAILED,
        );
    }

    protected function updatePaymentAfterCapture(
        StripeTransfer $stripeTransfer,
        StripeCaptureRequestTransfer $stripeCaptureRequestTransfer,
        StripeCaptureResponseTransfer $stripeCaptureResponseTransfer,
    ): void {
        // TODO: Define status constants in StripeConfig based on payment service provider specific statuses.
        // Extract the appropriate status from $stripeCaptureResponseTransfer and pass it to updatePaymentStatus.
        // e.g.
        // $this->entityManager->updatePaymentStatus(
        //     $stripeTransfer->getIdStripeOrFail(),
        //     StripeConfig::PAYMENT_STATUS_CAPTURED,
        //     $stripeCaptureResponseTransfer->getProviderReference(),
        // );
        $this->entityManager->updatePaymentStatus(
            $stripeTransfer->getIdStripeOrFail(),
            '',
        );
    }

    protected function buildCancelRequest(StripeTransfer $stripeTransfer): StripeCancelRequestTransfer
    {
        // TODO: Compose the request transfer from data in the StripeTransfer.
        // If your payment service provider requires additional data not available in StripeTransfer,
        // add additional parameters to this method signature and pass them from executeCancelCommand.
        // e.g.
        // return (new StripeCancelRequestTransfer())
        //     ->setProviderReference($stripeTransfer->getProviderReference())
        //     ->setCancellationReason('Customer requested cancellation');
        return (new StripeCancelRequestTransfer())->fromArray($stripeTransfer->toArray(), true);
    }

    protected function handleCancelError(
        StripeTransfer $stripeTransfer,
        StripeCancelRequestTransfer $stripeCancelRequestTransfer,
        ?StripeApiErrorResponseTransfer $stripeApiErrorResponseTransfer
    ): void {
        // TODO: Define status constants in StripeConfig based on payment service provider specific statuses.
        // Extract error details from $stripeApiErrorResponseTransfer if needed and pass appropriate status to updatePaymentStatus.
        // You may need to handle different error types with different status constants.
        // e.g.
        // $this->entityManager->updatePaymentStatus(
        //     $stripeTransfer->getIdStripeOrFail(),
        //     StripeConfig::PAYMENT_STATUS_CANCEL_FAILED,
        //     $stripeApiErrorResponseTransfer?->getProviderReference(),
        // );
        $this->entityManager->updatePaymentStatus(
            $stripeTransfer->getIdStripeOrFail(),
            StripeConfig::PAYMENT_STATUS_CANCEL_FAILED,
        );
    }

    protected function updatePaymentAfterCancel(
        StripeTransfer $stripeTransfer,
        StripeCancelRequestTransfer $stripeCancelRequestTransfer,
        StripeCancelResponseTransfer $stripeCancelResponseTransfer
    ): void {
        // TODO: Define status constants in StripeConfig based on payment service provider specific statuses.
        // Extract the appropriate status from $stripeCancelResponseTransfer and pass it to updatePaymentStatus.
        // e.g.
        // $this->entityManager->updatePaymentStatus(
        //     $stripeTransfer->getIdStripeOrFail(),
        //     StripeConfig::PAYMENT_STATUS_CANCELLED,
        //     $stripeCancelResponseTransfer->getProviderReference(),
        // );
        $this->entityManager->updatePaymentStatus(
            $stripeTransfer->getIdStripeOrFail(),
            '',
        );
    }
}
