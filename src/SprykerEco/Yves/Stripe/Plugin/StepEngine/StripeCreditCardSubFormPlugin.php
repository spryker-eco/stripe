<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Yves\Stripe\Plugin\StepEngine;

use SprykerEco\Yves\Kernel\AbstractPlugin;
use SprykerEco\Yves\StepEngine\Dependency\Form\SubFormInterface;
use SprykerEco\Yves\StepEngine\Dependency\Plugin\Form\SubFormPluginInterface;
use SprykerEco\Yves\Stripe\Form\DataProvider\StripeCreditCardDataProvider;

/**
 * @method \SprykerEco\Yves\Stripe\StripeFactory getFactory()
 */
class StripeCreditCardSubFormPlugin extends AbstractPlugin implements SubFormPluginInterface
{
    public function createSubForm(): SubFormInterface
    {
        return $this->getFactory()->createStripeCreditCardSubForm();
    }

    public function createSubFormDataProvider(): StripeCreditCardDataProvider
    {
        return $this->getFactory()->createStripeCreditCardDataProvider();
    }
}
