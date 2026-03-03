<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe\Business\Payment;

use ArrayObject;
use Generated\Shared\Transfer\PaymentMethodsTransfer;
use Generated\Shared\Transfer\StripePaymentMethodsRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerEco\Client\Stripe\StripeClientInterface;
use SprykerEco\Shared\Stripe\StripeConfig as SharedStripeConfig;
use SprykerEco\Zed\Stripe\StripeConfig;

class PaymentMethodFilter implements PaymentMethodFilterInterface
{
    public function __construct(
        protected StripeClientInterface $stripeClient,
        protected StripeConfig $stripeConfig,
    ) {
    }

    public function filterPaymentMethods(
        PaymentMethodsTransfer $paymentMethodsTransfer,
        QuoteTransfer $quoteTransfer,
    ): PaymentMethodsTransfer {
        $filteredMethods = [];

        $stripeAvailablePaymentMethods = $this->getStripeAvailablePaymentMethods($quoteTransfer);

        foreach ($paymentMethodsTransfer->getMethods() as $paymentMethodTransfer) {
            if ($paymentMethodTransfer->getPaymentProvider()?->getPaymentProviderKey() !== SharedStripeConfig::PAYMENT_PROVIDER_NAME) {
                $filteredMethods[] = $paymentMethodTransfer;

                continue;
            }

            if ($this->isPaymentMethodAllowed($paymentMethodTransfer->getMethodName(), $stripeAvailablePaymentMethods)) {
                $filteredMethods[] = $paymentMethodTransfer;
            }
        }

        return $paymentMethodsTransfer->setMethods(new ArrayObject($filteredMethods));
    }

    /**
     * @param string $paymentMethodName
     * @param array<string> $stripeAvailablePaymentMethods
     *
     * @return bool
     */
    protected function isPaymentMethodAllowed(string $paymentMethodName, array $stripeAvailablePaymentMethods): bool
    {
        // TODO: Replace placeholder return value with actual check logic.
        // Check if the current payment method is among the allowed methods returned by your payment service provider.
        // The available methods list comes from getStripeAvailablePaymentMethods().
        // e.g.
        // return in_array($paymentMethodName, $stripeAvailablePaymentMethods, true);
        return true; // Placeholder - replace with actual check
    }

    protected function buildPaymentMethodsRequest(QuoteTransfer $quoteTransfer): StripePaymentMethodsRequestTransfer
    {
        // TODO: Compose the request transfer from data in the QuoteTransfer.
        // Include data required by your payment service provider to determine available payment methods
        // (e.g., total amount, currency, customer location, billing address).
        // If additional data is needed beyond QuoteTransfer, add parameters to this method signature.
        // e.g.
        // return (new StripePaymentMethodsRequestTransfer())
        //     ->setAmount($quoteTransfer->getTotals()->getGrandTotal())
        //     ->setCurrency($quoteTransfer->getCurrency()->getCode())
        //     ->setCountryCode($quoteTransfer->getBillingAddress()->getIso2Code());
        return (new StripePaymentMethodsRequestTransfer());
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return array<string>
     */
    public function getStripeAvailablePaymentMethods(QuoteTransfer $quoteTransfer): array
    {
        $stripePaymentMethodsRequestTransfer = $this->buildPaymentMethodsRequest($quoteTransfer);
        $stripePaymentMethodsResponseTransfer = $this->stripeClient->getPaymentMethods($stripePaymentMethodsRequestTransfer);

        if (!$stripePaymentMethodsResponseTransfer->getIsSuccess()) {
            // If an error occurred, no payment methods are available, return empty array.
            return [];
        }

        // TODO: Replace placeholder return value with actual payment methods extraction.
        // Extract the list of available payment method names from the payment service provider response.
        // The returned array should contain payment method identifiers that match your configured method names.
        // e.g.
        // return $stripePaymentMethodsResponseTransfer->getPaymentMethods();
        return []; // Placeholder - replace with actual payment methods list
    }
}
