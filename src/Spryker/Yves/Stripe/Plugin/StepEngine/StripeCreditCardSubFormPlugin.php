<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
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
