<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\Stripe;

use SprykerEco\Client\Quote\QuoteClientInterface;
use SprykerEco\Yves\Kernel\AbstractFactory;
use SprykerEco\Client\Stripe\StripeClientInterface;
use SprykerEco\Yves\Stripe\Form\DataProvider\StripeCreditCardDataProvider;
use SprykerEco\Yves\Stripe\Form\DataProvider\StripeInvoiceDataProvider;
use SprykerEco\Yves\Stripe\Form\StripeCreditCardSubForm;
use SprykerEco\Yves\Stripe\Form\StripeInvoiceSubForm;

/**
 * @method \SprykerEco\Yves\Stripe\StripeConfig getConfig()
 */
class StripeFactory extends AbstractFactory
{
    public function createStripeCreditCardSubForm(): StripeCreditCardSubForm
    {
        return new StripeCreditCardSubForm();
    }

    public function createStripeCreditCardDataProvider(): StripeCreditCardDataProvider
    {
        return new StripeCreditCardDataProvider();
    }

    public function createStripeInvoiceSubForm(): StripeInvoiceSubForm
    {
        return new StripeInvoiceSubForm();
    }

    public function createStripeInvoiceDataProvider(): StripeInvoiceDataProvider
    {
        return new StripeInvoiceDataProvider();
    }

    public function getStripeClient(): StripeClientInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::CLIENT_STRIPE);
    }

    public function getQuoteClient(): QuoteClientInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::CLIENT_QUOTE);
    }
}
