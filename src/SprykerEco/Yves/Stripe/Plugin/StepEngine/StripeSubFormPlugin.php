<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Plugin\StepEngine;

use Spryker\Yves\Kernel\AbstractPlugin;
use Spryker\Yves\StepEngine\Dependency\Form\SubFormInterface;
use Spryker\Yves\StepEngine\Dependency\Plugin\Form\SubFormPluginInterface;
use SprykerEco\Yves\Stripe\Form\DataProvider\StripeFormDataProvider;

/**
 * @method \SprykerEco\Yves\Stripe\StripeFactory getFactory()
 */
class StripeSubFormPlugin extends AbstractPlugin implements SubFormPluginInterface
{
    /**
     * {@inheritDoc}
     */
    public function createSubForm(): SubFormInterface
    {
        return $this->getFactory()->createStripeSubForm();
    }

    /**
     * {@inheritDoc}
     */
    public function createSubFormDataProvider(): StripeFormDataProvider
    {
        return $this->getFactory()->createStripeFormDataProvider();
    }
}
