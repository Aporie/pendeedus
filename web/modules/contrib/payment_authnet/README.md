# Payment Authorize.net

**Drupal 8 version of Authorize.net payment method controller for the Payment
module.**

A payment method using [Authorize.Net][1] for transactions handled by the
Payment API. Uses [AuthorizeNet/sdk-php][2].

#### Dependencies:
* [Payment][3]
* [Creditfield][4]

You may need to apply the following patches for [Payment][3] and [Currency][5]
modules in order to make Payment form work:

1. [Payment][3]
   1. [#2841035](https://www.drupal.org/project/payment/issues/2841035):
      [Payment entity fields don't get saved][6].
   2. [#2840698](https://www.drupal.org/project/payment/issues/2840698): 
      [Ajax broken in payment_form submodule][7].
   3. [#2701505](https://www.drupal.org/project/payment/issues/2701505):
      [Payment execution status not saved][8].
2. [Currency][5]
   1. [#2939752](https://www.drupal.org/project/currency/issues/2939752):
      [AmountFormatterManager uses wrong config][9]

#### Supported operations:
* [Authorize a Credit Card][10]
* [Capture a Previously Authorized Amount][10]
* [Charge a Credit Card][10]
* [Refund a Transaction][10]
* Partial Refunds
* [Void A Transaction][10]

#### Similar projects:
* [Authorize.net for Payment (Drupal 7)][11]

#### Installation&Configuration
1. Download and install the module (you may use the following
   [instructions][12]).
2. Go to `/admin/config/services/payment/authnet` and add one or more
   Authorize.net profiles.
3. Test just created profiles at
   `/admin/config/services/payment/authnet/connection-test`
4. Update additional fields configuration at
   `/admin/config/services/payment/authnet/additional-fields` if needed.
5. Go to `/admin/config/services/payment/method` and create at least one
   "Authorize.net" payment method.

**In order to allow payments for users for created by someone's else fieldable
entity (e.g node):**

1. Enable module "Payment Form Field"
2. Add new field of type "Payment Form" to the appropriate bundle of needed
   entity type.

#### Supporting organizations:
[FFW Agency](https://www.drupal.org/ffw-agency)

-----
[1]: https://authorize.net/ "Authorize.Net"
[2]: https://github.com/AuthorizeNet/sdk-php "AuthorizeNet/sdk-php"
[3]: https://www.drupal.org/project/payment "Payment"
[4]: https://www.drupal.org/project/creditfield "Creditfield"
[5]: https://www.drupal.org/project/currency "Currency"
[6]: https://www.drupal.org/files/issues/fix_fieldable.patch
[7]: https://www.drupal.org/files/issues/2019-04-16/ajax-broken-2840698-23.patch
[8]: https://www.drupal.org/files/issues/payment-payment-execution-status-not-saved-2701505-3.patch
[9]: https://www.drupal.org/files/issues/2939752-2-fix_amount_formatter_config.patch
[10]: https://developer.authorize.net/api/reference/#payment-transactions
[11]: https://www.drupal.org/project/authnet_payment
[12]: https://www.drupal.org/docs/user_guide/en/extend-module-install.html
