<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "add payment status" route.
 */
class AddPaymentStatus extends ControllerBase {

  /**
   * The payment status storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentStatusStorage;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Entity\EntityStorageInterface $payment_status_storage
   *   The payment status storage.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder, EntityStorageInterface $payment_status_storage) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->paymentStatusStorage = $payment_status_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($container->get('entity.form_builder'), $entity_type_manager->getStorage('payment_status'));
  }

  /**
   * Displays a payment status add form.
   *
   * @return array
   */
  public function execute() {
    $payment_status = $this->paymentStatusStorage->create();

    return $this->entityFormBuilder->getForm($payment_status);
  }

}
