Payment offers the following plugin types for which plugins can be provided by
other modules:

# Payment method
Payment methods are responsible for executing, refunding, and capturing
payments. They are classes that implement
`\Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface` and live in
`\Drupal\$module\Plugin\Payment\Method`, where `$module` is the machine name of
the module that provides the plugins. The classes are annotated using
`\Drupal\payment\Annotations\PaymentMethod`.

One payment method instance belongs to one payment entity and vice versa. See
`\Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface::getPayment()`,
`\Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface::setPayment()`,
`\Drupal\payment\Entity\PaymentInterface::getPaymentMethod()`, and
`\Drupal\payment\Entity\PaymentInterface::setPaymentMethod()`.

If a plugin provides configuration, it must also provide a configuration schema
for this configuration of which the type is
`plugin.plugin_configuration.payment_method.[plugin_id]`, where `[plugin_id]` 
is the plugin's ID.

Plugin definitions are cached using the `payment_method` tag.

See the [drupal.org handbook](https://www.drupal.org/node/1905070) for more 
information about configuration schemas.

# Payment method configuration
Payment method configuration plugins allow you to configure payment method
configuration entities through the administrative user interface. They are
classes that implement
`\Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface` and live in
`\Drupal\$module\Plugin\Payment\Method`, where `$module` is the machine name of 
the module that provides the plugins. The classes are annotated using
`\Drupal\payment\Annotations\PaymentMethodConfiguration`. They provide the form
elements to collect configuration that is specific to a particular payment
method plugin, so those payment method plugins' derivers can provide derivative
plugin definitions based on the payment method configuration plugin
configuration that is stored in payment method configuration entities.

One payment method configuration instance belongs to one payment method
configuration entity and vice versa. See
`\Drupal\payment\Entity\PaymentMethodConfigurationInterface::getPluginId()`,
`\Drupal\payment\Entity\PaymentMethodConfigurationInterface::getPluginConfiguration()`,
and
`\Drupal\payment\Entity\PaymentMethodConfigurationInterface::setPluginConfiguration()`.

If a plugin provides configuration, it must also provide a configuration schema
for this configuration of which the type is
`plugin.plugin_configuration.payment_method_configuration.[plugin_id]`, where
`[plugin_id]` is the plugin's ID.

Plugin definitions are cached using the `payment_method_configuration` tag.

See the [drupal.org handbook](https://www.drupal.org/node/1905070) for more 
information about configuration schemas.

See the [drupal.org handbook](https://www.drupal.org/node/1653226) for more 
information about derivative plugins.

# Payment type
Payment types provide information about the type a payment entity is of and the
context it was created in. They are classes that implement
`\Drupal\payment\Plugin\Payment\Type\PaymentTypeInterface` and live in
`\Drupal\$module\Plugin\Payment\Type`, where `$module` is the machine name of 
the module that provides the plugins. The classes are annotated using
`\Drupal\payment\Annotations\PaymentType`.

One payment type instance belongs to one payment entity and vice versa. See
`\Drupal\payment\Plugin\Payment\Type\PaymentTypeInterface::getPayment()`,
`\Drupal\payment\Plugin\Payment\Type\PaymentTypeInterface::setPayment()`,
`\Drupal\payment\Entity\PaymentInterface::getPaymentType()`, and
`\Drupal\payment\Entity\PaymentInterface::setPaymentType()`.

If a plugin provides configuration, it must also provide a configuration schema
for this configuration of which the type is
`plugin.plugin_configuration.payment_method_type.[plugin_id]`, where 
`[plugin_id]` is the plugin's ID.

Plugin definitions are cached using the `payment_type` tag.

See the [drupal.org handbook](https://www.drupal.org/node/1905070) for more 
information about configuration schemas.

# Payment status
Payment statuses describe a payment entity's past and current status. They are
classes that implement
`\Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface`. Plugins are
defined in a `$module.payment.status.yml` file that is located in the root
folder of the module that provides the plugins, where `$module` is the machine 
name of that module. The definitions in the YAML file are keyed by plugin ID 
and contain the following keys per plugin:
- class (optional): the fully qualified name of the plugin class. Defaults to
  `\Drupal\payment\Plugin\Payment\Status\DefaultPaymentStatus`.
- description (optional): The US English human-readable description.
- label (required): The US English human-readable label.
- operations_provider (optional): The fully qualified name of a class that must
  implement `\Drupal\plugin\PluginOperationsProviderInterface` and may implement 
  `\Drupal\Core\DependencyInjection\ContainerInjectionInterface`.
- parent_id (optional): payment_no_money_transferred

Multiple payment status instances belong to one payment entity, but only one
payment entity belongs to any payment status instance. See
`\Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface::getPayment()`,
`\Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface::setPayment()`,
`\Drupal\payment\Entity\PaymentInterface::getPaymentStatus()`,
`\Drupal\payment\Entity\PaymentInterface::setPaymentStatus()`,
`\Drupal\payment\Entity\PaymentInterface::getPaymentStatuses()`, and
`\Drupal\payment\Entity\PaymentInterface::setPaymentStatuses()`.

If a plugin provides configuration, it must also provide a configuration schema
for this configuration of which the type is
`plugin.plugin_configuration.payment_method_status.[plugin_id]`, where
`[plugin_id]` is the plugin's ID.

Plugin definitions are cached using the `payment_status` tag.

See the [drupal.org handbook](https://www.drupal.org/node/1905070) for more 
information about configuration schemas.

# Line item
Line items describe the amounts that make up a payment's total amount, just 
like line items on an invoice. They are classes that implement
`\Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface` and live in
`\Drupal\$module\Plugin\Payment\LineItem`, where `$module` is the machine name 
of the module that provides the plugins. The classes are annotated using
`\Drupal\payment\Annotations\PaymentLineItem`.

Multiple line item instances belong to one payment entity, but only one payment
entity belongs to any payment status instance. See
`\Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface::getPayment()`,
`\Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface::setPayment()`,
`\Drupal\payment\Entity\PaymentInterface::getLineItem()`,
`\Drupal\payment\Entity\PaymentInterface::setLineItem()`,
`\Drupal\payment\Entity\PaymentInterface::getLineItems()`,
`\Drupal\payment\Entity\PaymentInterface::getLineItemsByType()`, and
`\Drupal\payment\Entity\PaymentInterface::setLineItems()`.

If a plugin provides configuration, it must also provide a configuration schema
for this configuration of which the type is
`plugin.plugin_configuration.payment_method_line_item.[plugin_id]`, where
`[plugin_id]` is the plugin's ID.

Plugin definitions are cached using the `payment_line_item` tag.

See the [drupal.org handbook](https://www.drupal.org/node/1905070) for more 
information about configuration schemas.
