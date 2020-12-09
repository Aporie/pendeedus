<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Method\Unavailable.
 */

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\OperationResult;

/**
 * A payment method controller that essentially disables payment methods.
 *
 * This is a 'placeholder' controller that returns defaults and doesn't really
 * do anything else. It is used when no working controller is available for a
 * payment method, so other modules don't have to check for that.
 *
 * @PaymentMethod(
 *   active = FALSE,
 *   id = "payment_unavailable",
 *   label = @Translation("Unavailable"),
 *   module = "payment"
 * )
 */
class Unavailable extends PluginBase implements PaymentMethodInterface {

  /**
   * The payment this payment method is for.
   *
   * @var \Drupal\payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSupportedCurrencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentExecutionResult() {
    return new OperationResult();
  }

  /**
   * {@inheritdoc}
   */
  public function executePaymentAccess(AccountInterface $account) {
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function executePayment() {
    throw new \RuntimeException('This plugin cannot execute payments.');
  }

  /**
   * Gets the payment this payment method is for.
   *
   * @return \Drupal\payment\Entity\PaymentInterface
   */
  public function getPayment() {
    return $this->payment;
  }

  /**
   * Gets the payment this payment method is for.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *
   * @return $this
   */
  public function setPayment(PaymentInterface $payment) {
    $this->payment = $payment;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
