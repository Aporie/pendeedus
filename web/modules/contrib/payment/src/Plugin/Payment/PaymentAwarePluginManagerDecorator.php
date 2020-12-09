<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\PaymentAwarePluginManagerDecorator.
 */

namespace Drupal\payment\Plugin\Payment;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\PaymentAwareInterface;
use Drupal\plugin\PluginManager\PluginManagerDecorator;

/**
 * Provides a payment-aware plugin manager decorator.
 */
class PaymentAwarePluginManagerDecorator extends PluginManagerDecorator {

  /**
   * The payment to inject into plugin instances.
   *
   * @var \Drupal\payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * Creates a new instance.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The payment to inject into payment-aware plugin instances.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The decorated plugin manager.
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface|null $discovery
   *   A plugin discovery to use instead of the decorated plugin manager, or
   *   NULL to use the decorated plugin manager.
   */
  public function __construct(PaymentInterface $payment, PluginManagerInterface $plugin_manager, DiscoveryInterface $discovery = NULL) {
    parent::__construct($plugin_manager, $discovery);
    $this->payment = $payment;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin = $this->decoratedFactory->createInstance($plugin_id, $configuration);
    if ($plugin instanceof PaymentAwareInterface) {
      $plugin->setPayment($this->payment);
    }

    return $plugin;
  }

}
