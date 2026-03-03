<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\StripeApiErrorResponseTransfer;
use Generated\Shared\Transfer\StripeAuthorizeRequestTransfer;
use Generated\Shared\Transfer\StripeAuthorizeResponseTransfer;
use Generated\Shared\Transfer\StripeTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Client\Stripe\StripeClientInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\StripeConfig;
use SprykerEco\Zed\Stripe\Persistence\StripeEntityManagerInterface;

class PaymentAuthorizer implements PaymentAuthorizerInterface
{
    public function __construct(
        protected StripeClientInterface $client,
        protected PaymentReaderInterface $paymentReader,
        protected StripeEntityManagerInterface $entityManager,
        protected StripeConfig $config,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function executePostSaveHook(
        QuoteTransfer $quoteTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer,
    ): void {
        if ($quoteTransfer->getPayment()->getPaymentProvider() !== SharedStripeConfig::PAYMENT_PROVIDER_NAME) {
            return;
        }

        $stripeTransfer = $this->paymentReader->findPaymentByIdSalesOrder(
            $checkoutResponseTransfer->getSaveOrderOrFail()->getIdSalesOrderOrFail(),
        );

        if ($stripeTransfer === null) {
            return;
        }

        $stripeAuthorizeRequestTransfer = $this->buildAuthorizeRequest($stripeTransfer);

        $stripeAuthorizeResponseTransfer = $this->client->authorize($stripeAuthorizeRequestTransfer);

        if (!$stripeAuthorizeResponseTransfer->getIsSuccess()) {
            $this->handleAuthorizationError($stripeTransfer, $stripeAuthorizeResponseTransfer->getErrorResponse());

            return;
        }

        $this->updatePaymentAfterAuthorization($stripeTransfer, $stripeAuthorizeResponseTransfer);
    }

    protected function buildAuthorizeRequest(StripeTransfer $stripeTransfer): StripeAuthorizeRequestTransfer
    {
        // TODO: Compose the request transfer from data in the StripeTransfer.
        // If your payment service provider requires additional data not available in StripeTransfer,
        // add additional parameters to this method signature (e.g., QuoteTransfer, CheckoutResponseTransfer)
        // and pass them from executePostSaveHook.
        // e.g.
        // return (new StripeAuthorizeRequestTransfer())
        //     ->setAmount($stripeTransfer->getAmount())
        //     ->setCurrency($stripeTransfer->getCurrency())
        //     ->setPaymentMethodToken($stripeTransfer->getPaymentMethodToken())
        //     ->setOrderReference($stripeTransfer->getOrderReference());
        return (new StripeAuthorizeRequestTransfer());
    }

    protected function handleAuthorizationError(
        StripeTransfer $stripeTransfer,
        ?StripeApiErrorResponseTransfer $stripeApiErrorResponseTransfer
    ): void {
        // TODO: Define status constants in StripeConfig based on payment service provider specific statuses.
        // Extract error details from $stripeApiErrorResponseTransfer if needed and pass appropriate status to updatePaymentStatus.
        // You may need to handle different error types with different status constants.
        // e.g.
        // $this->entityManager->updatePaymentStatus(
        //     $stripeTransfer->getIdStripeOrFail(),
        //     $this->config::PAYMENT_STATUS_AUTHORIZATION_FAILED,
        //     $stripeApiErrorResponseTransfer?->getProviderReference(),
        // );
        $this->entityManager->updatePaymentStatus(
            $stripeTransfer->getIdStripeOrFail(),
            $this->config::PAYMENT_STATUS_AUTHORIZATION_FAILED,
        );
    }

    protected function updatePaymentAfterAuthorization(
        StripeTransfer $stripeTransfer,
        StripeAuthorizeResponseTransfer $stripeAuthorizeResponseTransfer
    ): void {
        // TODO: Define status constants in StripeConfig based on payment service provider specific statuses.
        // Extract the appropriate status from $stripeAuthorizeResponseTransfer and pass it to updatePaymentStatus.
        // e.g.
        // $this->entityManager->updatePaymentStatus(
        //     $stripeTransfer->getIdStripeOrFail(),
        //     $this->config::PAYMENT_STATUS_AUTHORIZED,
        //     $stripeAuthorizeResponseTransfer->getProviderReference(),
        // );
        $this->entityManager->updatePaymentStatus(
            $stripeTransfer->getIdStripeOrFail(),
            $this->config::PAYMENT_STATUS_AUTHORIZED,
        );
    }
}
