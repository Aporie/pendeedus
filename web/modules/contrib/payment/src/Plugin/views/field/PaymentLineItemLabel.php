<?php

namespace Drupal\payment\Plugin\views\field;

use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a payment line item's plugin ID as the plugin's label.
 *
 * @ViewsField("payment_line_item_label")
 */
class PaymentLineItemLabel extends FieldPluginBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface
   */
  protected $paymentLineItemManager;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface $payment_line_item_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PaymentLineItemManagerInterface $payment_line_item_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->paymentLineItemManager = $payment_line_item_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('plugin.manager.payment.line_item'));
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $plugin_id = $this->getValue($values);
    $plugin_definition = $this->paymentLineItemManager->getDefinition($plugin_id);

    return $plugin_definition['label'];
  }

}
