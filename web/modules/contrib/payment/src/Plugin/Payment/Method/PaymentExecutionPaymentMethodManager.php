<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Method\PaymentExecutionPaymentMethodManager.
 */

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\PaymentAwarePluginManagerDecorator;

/**
 * Provides a payment method manager for executing a payment.
 *
 * @see \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface
 */
class PaymentExecutionPaymentMethodManager extends PaymentAwarePluginManagerDecorator implements PaymentMethodManagerInterface {

  /**
   * The account for which to check execution access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The decorated payment method manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface
   */
  protected $decoratedPaymentMethodManager;

  /**
   * Creates a new instance.
   *
   * @param \Drupal\payment\Entity\PaymentInterface
   *   The payment to check execution access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check payment execution access for.
   * @param \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface $payment_method_manager
   *   The payment method manager.
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface|null $discovery
   *   A plugin discovery to use instead of the decorated plugin manager, or
   *   NULL to use the decorated plugin manager.
   */
  public function __construct(PaymentInterface $payment, AccountInterface $account, PaymentMethodManagerInterface $payment_method_manager, DiscoveryInterface $discovery = NULL) {
    parent::__construct($payment, $payment_method_manager, $discovery);
    $this->account = $account;
    $this->decoratedPaymentMethodManager = $payment_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function processDecoratedDefinitions(array $decorated_definitions) {
    $processed_definitions = [];
    foreach ($decorated_definitions as $plugin_id => $decorated_definition) {
      /** @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface $payment_method */
      $payment_method = $this->createInstance($plugin_id);

      if ($payment_method->executePaymentAccess($this->account)->isAllowed()) {
        $processed_definitions[$plugin_id] = $decorated_definition;
      }
    }

    return $processed_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperationsProvider($plugin_id) {
    if ($this->hasDefinition($plugin_id)) {
      return $this->decoratedPaymentMethodManager->getOperationsProvider($plugin_id);
    }
    else {
      throw new PluginNotFoundException($plugin_id);
    }
  }

}
