<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe;

use Spryker\Client\Cart\CartClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;
use SprykerEco\Client\Stripe\StripeClientInterface;
use SprykerEco\Yves\Stripe\Form\DataProvider\StripeFormDataProvider;
use SprykerEco\Yves\Stripe\Form\StripeSubForm;

/**
 * @method \SprykerEco\Yves\Stripe\StripeConfig getConfig()
 */
class StripeFactory extends AbstractFactory
{
    public function createStripeSubForm(): StripeSubForm
    {
        return new StripeSubForm();
    }

    public function createStripeFormDataProvider(): StripeFormDataProvider
    {
        return new StripeFormDataProvider();
    }

    public function getStripeClient(): StripeClientInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::CLIENT_STRIPE);
    }

    public function getCartClient(): CartClientInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::CLIENT_CART);
    }
}
