<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Zed\Stripe;

use SprykerEco\Zed\Kernel\AbstractBundleConfig;

class StripeConfig extends AbstractBundleConfig
{
    public const string PAYMENT_STATUS_NEW = 'new';

    public const string PAYMENT_STATUS_AUTHORIZATION_PENDING = 'authorization_pending';

    public const string PAYMENT_STATUS_AUTHORIZED = 'authorized';

    public const string PAYMENT_STATUS_AUTHORIZATION_FAILED = 'authorization_failed';

    public const string PAYMENT_STATUS_CAPTURE_PENDING = 'capture_pending';

    public const string PAYMENT_STATUS_CAPTURED = 'captured';

    public const string PAYMENT_STATUS_CAPTURE_FAILED = 'capture_failed';

    public const string PAYMENT_STATUS_REFUND_PENDING = 'refund_pending';

    public const string PAYMENT_STATUS_CANCELED = 'canceled';

    public const string PAYMENT_STATUS_CANCEL_FAILED = 'cancel_failed';

    public const string TRANSACTION_TYPE_AUTHORIZE = 'authorize';

    public const string TRANSACTION_TYPE_CAPTURE = 'capture';

    public const string TRANSACTION_TYPE_CANCEL = 'cancel';
}
