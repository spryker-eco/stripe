<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe;

use Spryker\Client\Quote\QuoteClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;
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
