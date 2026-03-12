# Stripe Module
[![Latest Stable Version](https://poser.pugx.org/spryker-eco/algolia/v/stable.svg)](https://packagist.org/packages/spryker-eco/algolia)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

For Webhooks locally

1. Install [Stripe CLI](https://docs.stripe.com/stripe-cli)
2. Run:
```bash
stripe listen --forward-to http://yves.eu.spryker.local/stripe/notification
```
3. Copy signing secret from the output. Use it for `STRIPE_WEBHOOK_SECRET`.

Configure Stripe module:

```php
// config/Shared/config_local.php

// Get these from https://dashboard.stripe.com/apikeys and Dashboard > Webhooks
$config[StripeConstants::STRIPE_SECRET_KEY] = 'sk_test_***';
$config[StripeConstants::STRIPE_PUBLISHABLE_KEY] = 'pk_test_***';
$config[StripeConstants::STRIPE_WEBHOOK_SECRET] = 'whsec_***';
$config[StripeConstants::STRIPE_BUSINESS_MODEL] = 'direct'; // or marketplace
```


## Support

For issues or questions:
- Check [Spryker Stripe documentation](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/base-shop/third-party-integrations/stripe/stripe)
- Review [Stripe documentation](https://docs.stripe.com/)
- Contact Spryker support

## Development

To check/fix code style and run static analysis, use:

```bash
composer cs-fix # can be used standalone
composer phpstan # only works together with Spryker project (uses autoloader from it)
```

For test execution, check the details in [tests/README.md](tests/README.md) file.


## License

This module is licensed under the same license as [Spryker Commerce OS](LICENSE).
