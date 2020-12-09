<?php

namespace Drupal\payment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\payment\LineItemCollectionInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface as PluginPaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface as PluginPaymentStatusInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines a payment entity type .
 */
interface PaymentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, LineItemCollectionInterface {

  /**
   * Executes the payment.
   *
   * @return \Drupal\payment\OperationResultInterface
   */
  public function execute();

  /**
   * Returns the timestamp of the entity creation.
   *
   * @return int
   */
  public function getCreatedTime();

  /**
   * Gets the payment's type plugin.
   *
   * @return \Drupal\payment\Plugin\Payment\Type\PaymentTypeInterface
   */
  public function getPaymentType();

  /**
   * Sets/replaces all statuses without notifications.
   *
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface[] $payment_statuses
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   */
  public function setPaymentStatuses(array $payment_statuses);

  /**
   * Sets a status.
   *
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface $payment_status
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   */
  public function setPaymentStatus(PluginPaymentStatusInterface $payment_status);

  /**
   * Gets all payment statuses.
   *
   * @return \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface[]
   *   The statuses are ordered by time with the newest last.
   */
  public function getPaymentStatuses();

  /**
   * Gets the current payment status.
   *
   * @return \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface
   */
  public function getPaymentStatus();

  /**
   * Gets the payment method plugin.
   *
   * @return \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface
   */
  public function getPaymentMethod();

  /**
   * Gets the payment method plugin.
   *
   * @param \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface
   *
   * @return static
   */
  public function setPaymentMethod(PluginPaymentMethodInterface $payment_method);

  /**
   * Gets the line items' currency.
   *
   * @return \Drupal\currency\Entity\CurrencyInterface
   */
  public function getCurrency();
}
