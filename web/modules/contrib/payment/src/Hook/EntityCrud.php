<?php

namespace Drupal\payment\Hook;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;

/**
 * Implements hook_entity_CRUD().
 */
class EntityCrud {

  /**
   * The payment method manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * The payment status plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface $payment_method_manager
   *   The payment method plugin manager.
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface $payment_status_manager
   *   The payment status plugin manager.
   */
  public function __construct(PaymentMethodManagerInterface $payment_method_manager, PaymentStatusManagerInterface $payment_status_manager) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->paymentStatusManager = $payment_status_manager;
  }

  /**
   * Invokes the implementation.
   */
  public function invoke(EntityInterface $entity) {
    if ($entity->getEntityTypeId() == 'payment_method_configuration') {
      $manager = $this->paymentMethodManager;
    }
    elseif ($entity->getEntityTypeId() == 'payment_status') {
      $manager = $this->paymentStatusManager;
    }
    if (isset($manager) && $manager instanceof CachedDiscoveryInterface) {
      $manager->clearCachedDefinitions();
    }
  }

}
