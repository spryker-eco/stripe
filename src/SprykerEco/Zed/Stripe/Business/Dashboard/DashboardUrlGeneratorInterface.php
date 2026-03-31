<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\Stripe\Business\Dashboard;

interface DashboardUrlGeneratorInterface
{
    public function generateDashboardUrl(string $merchantReference): ?string;
}
