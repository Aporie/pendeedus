<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Status\ConfigOperationsProvider.
 */

namespace Drupal\payment\Plugin\Payment\Status;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\plugin\PluginOperationsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides payment status operations payment statuses based on config entities.
 */
class ConfigOperationsProvider implements PluginOperationsProviderInterface, ContainerInjectionInterface {

  /**
   * The payment status list builder.
   *
   * @var \Drupal\Core\Entity\EntityListBuilderInterface
   */
  protected $paymentStatusListBuilder;

  /**
   * The payment status storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentStatusStorage;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $payment_status_storage
   *   The payment status storage.
   * @param \Drupal\Core\Entity\EntityListBuilderInterface $payment_status_list_builder
   *   The payment status list builder.
   */
  public function __construct(EntityStorageInterface $payment_status_storage, EntityListBuilderInterface $payment_status_list_builder) {
    $this->paymentStatusListBuilder = $payment_status_list_builder;
    $this->paymentStatusStorage = $payment_status_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($entity_type_manager->getStorage('payment_status'), $entity_type_manager->getListBuilder('payment_status'));
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_id) {
    $entity_id = substr($plugin_id, 15);
    $entity = $this->paymentStatusStorage->load($entity_id);

    return $this->paymentStatusListBuilder->getOperations($entity);
  }
}
