<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Stripe;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StripeCustomerRequestTransfer;
use Generated\Shared\Transfer\StripeCustomerResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Zed\Stripe\Business\Client\StripeClientFactory;
use Stripe\Exception\ApiErrorException;

class StripeCustomers implements StripeCustomersInterface
{
    use LoggerTrait;

    public function __construct(protected StripeClientFactory $stripeClientFactory)
    {
    }

    public function searchOrCreate(StripeCustomerRequestTransfer $stripeCustomerRequestTransfer): StripeCustomerResponseTransfer
    {
        $stripeCustomerResponseTransfer = new StripeCustomerResponseTransfer();
        $stripeCustomerResponseTransfer->setIsSuccessful(false);

        $quoteTransfer = $stripeCustomerRequestTransfer->getQuoteOrFail();

        // Bank Account payments require a customer record. Without an email we skip customer creation.
        if (!$quoteTransfer->getCustomer()?->getEmail()) {
            return $stripeCustomerResponseTransfer;
        }

        try {
            $stripeClient = $this->stripeClientFactory->create();

            $searchResult = $stripeClient->customers->search([
                'query' => sprintf('email: "%s"', $quoteTransfer->getCustomerOrFail()->getEmailOrFail()),
            ]);

            if ($searchResult->count() === 0) {
                return $this->create($stripeCustomerRequestTransfer);
            }

            $customer = $searchResult->data[0];

            $stripeCustomerResponseTransfer->setIsSuccessful(true);
            $stripeCustomerResponseTransfer->setCustomerId($customer->id);
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error($apiErrorException, [
                QuoteTransfer::ORDER_REFERENCE => $quoteTransfer->getOrderReference(),
                QuoteTransfer::CUSTOMER_REFERENCE => $quoteTransfer->getCustomerReference(),
            ]);

            $stripeCustomerResponseTransfer->setMessage($apiErrorException->getMessage());
        }

        return $stripeCustomerResponseTransfer;
    }

    protected function create(StripeCustomerRequestTransfer $stripeCustomerRequestTransfer): StripeCustomerResponseTransfer
    {
        $quoteTransfer = $stripeCustomerRequestTransfer->getQuoteOrFail();
        $stripeCustomerResponseTransfer = new StripeCustomerResponseTransfer();
        $stripeCustomerResponseTransfer->setIsSuccessful(false);

        try {
            $stripeClient = $this->stripeClientFactory->create();

            $customer = $stripeClient->customers->create([
                'name' => sprintf('%s %s', $quoteTransfer->getCustomer()?->getFirstName(), $quoteTransfer->getCustomer()?->getLastName()),
                'email' => $quoteTransfer->getCustomerOrFail()->getEmailOrFail(),
                'metadata' => [
                    QuoteTransfer::CUSTOMER_REFERENCE => $quoteTransfer->getCustomerReference(),
                ],
            ]);

            $stripeCustomerResponseTransfer->setIsSuccessful(true);
            $stripeCustomerResponseTransfer->setCustomerId($customer->id);
        } catch (ApiErrorException $apiErrorException) {
            $this->getLogger()->error($apiErrorException, [
                QuoteTransfer::ORDER_REFERENCE => $quoteTransfer->getOrderReference(),
                QuoteTransfer::CUSTOMER_REFERENCE => $quoteTransfer->getCustomerReference(),
            ]);

            $stripeCustomerResponseTransfer->setMessage($apiErrorException->getMessage());
        }

        return $stripeCustomerResponseTransfer;
    }
}
