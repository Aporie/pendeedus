<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationInterface.
 */

namespace Drupal\payment\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * A payment method configuration plugin.
 *
 * Plugins can additionally implement the following interfaces:
 * - \Drupal\Component\Plugin\ConfigurableInterface
 *   Required if the plugin has any internal configuration, so it can be
 *   exported for recreation of the plugin at a later time.
 */
interface PaymentMethodConfigurationInterface extends PluginInspectionInterface, PluginFormInterface {

}
