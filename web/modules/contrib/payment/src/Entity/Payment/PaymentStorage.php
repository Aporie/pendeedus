<?php

namespace Drupal\payment\Entity\Payment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles storage for payment entities.
 */
class PaymentStorage extends SqlContentEntityStorage {

  /**
   * The payment status manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * The payment type manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface
   */
  protected $paymentTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $storage */
    $storage = parent::createInstance($container, $entity_type);
    $storage->paymentStatusManager = $container->get('plugin.manager.payment.status');
    $storage->paymentTypeManager = $container->get('plugin.manager.payment.type');
    return $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = parent::create($values);
    $payment_type = $this->paymentTypeManager->createInstance($values['bundle']);
    $payment_type->setPayment($payment);
    $payment->get('payment_type')->setValue($payment_type);
    $status = $this->paymentStatusManager->createInstance('payment_created')
      ->setCreated(time());
    $payment->setPaymentStatus($status);

    return $payment;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapToStorageRecord(ContentEntityInterface $entity, $table_name = NULL) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $entity;

    $record = parent::mapToStorageRecord($entity, $table_name);
    $deltas = [];
    foreach ($payment->getPaymentStatuses() as $delta => $item) {
      $deltas[] = $delta;
    }
    $record->current_payment_status_delta = max($deltas);

    return $record;
  }

}
