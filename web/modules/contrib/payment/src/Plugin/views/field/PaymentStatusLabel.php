<?php

namespace Drupal\payment\Plugin\views\field;

use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a payment status's plugin ID as the plugin's label.
 *
 * @ViewsField("payment_status_label")
 */
class PaymentStatusLabel extends FieldPluginBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface $payment_status_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PaymentStatusManagerInterface $payment_status_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->paymentStatusManager = $payment_status_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('plugin.manager.payment.status'));
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $plugin_id = $this->getValue($values);
    $plugin_definition = $this->paymentStatusManager->getDefinition($plugin_id);

    return $plugin_definition['label'];
  }

}
