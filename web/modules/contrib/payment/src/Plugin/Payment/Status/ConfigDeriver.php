<?php

namespace Drupal\payment\Plugin\Payment\Status;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives payment status plugin definitions based on configuration entities.
 */
class ConfigDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The payment status storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentStatusStorage;

  /**
   * Constructs a new instance.
   */
  public function __construct(EntityStorageInterface $payment_status_storage) {
    $this->paymentStatusStorage = $payment_status_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('entity_type.manager')->getStorage('payment_status'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\payment\Entity\PaymentStatusInterface[] $statuses */
    $statuses = $this->paymentStatusStorage->loadMultiple();
    foreach ($statuses as $status) {
      $this->derivatives[$status->id()] = array(
        'description' => $status->getDescription(),
        'label' => $status->label(),
        'parent_id' => $status->getParentId(),
      ) + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
