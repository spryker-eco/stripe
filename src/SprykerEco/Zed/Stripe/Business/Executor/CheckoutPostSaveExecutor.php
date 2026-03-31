<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Executor;

use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentInitializerInterface;
use SprykerEco\Zed\Stripe\Business\Payment\PaymentSaverInterface;
use SprykerEco\Zed\Stripe\StripeConfig;

class CheckoutPostSaveExecutor implements CheckoutPostSaveExecutorInterface
{
    public function __construct(
        protected PaymentInitializerInterface $paymentInitializer,
        protected PaymentSaverInterface $paymentSaver,
        protected StripeConfig $stripeConfig,
    ) {
    }

    public function executeCheckoutPostSaveHook(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        if ($quoteTransfer->getPaymentOrFail()->getPaymentProvider() !== SharedStripeConfig::PAYMENT_PROVIDER_NAME) {
            return;
        }

        $saveOrderTransfer = $checkoutResponseTransfer->getSaveOrderOrFail();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();

        $quoteTransfer->setOrderReference($orderReference);

        $intentResponse = $this->paymentInitializer->initializePayment($quoteTransfer);

        if (!$intentResponse->getIsSuccessful()) {
            $checkoutResponseTransfer
                ->setIsSuccess(false)
                ->addError(
                    (new CheckoutErrorTransfer())
                        ->setMessage('Stripe payment initialization failed. Please try again.'),
                );

            return;
        }

        $this->paymentSaver->savePayment(
            $quoteTransfer,
            $saveOrderTransfer,
            $intentResponse->getTransactionIdOrFail(),
        );

        $redirectUrl = $this->stripeConfig->getYvesBaseUrl() . SharedStripeConfig::ROUTE_PATH_PAYMENT . '?orderReference=' . rawurlencode($orderReference);

        $checkoutResponseTransfer
            ->setIsExternalRedirect(true)
            ->setRedirectUrl($redirectUrl);
    }
}
