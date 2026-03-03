<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\Stripe;

use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use SprykerEco\Client\Stripe\Zed\StripeStub;
use SprykerEco\Client\Stripe\Zed\StripeStubInterface;

/**
 * @method \SprykerEco\Client\Stripe\StripeConfig getConfig()
 */
class StripeFactory extends AbstractFactory
{
    public function createZedStub(): StripeStubInterface
    {
        return new StripeStub($this->getZedRequestClient());
    }

    protected function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(StripeDependencyProvider::CLIENT_ZED_REQUEST);
    }
}
