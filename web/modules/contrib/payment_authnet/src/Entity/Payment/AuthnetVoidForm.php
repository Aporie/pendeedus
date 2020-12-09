<?php

namespace Drupal\payment_authnet\Entity\Payment;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment refund form.
 */
class AuthnetVoidForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to cancel payment #@payment_id?', [
      '@payment_id' => $this->getEntity()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $result = parent::getDescription() . ' ' . $this->t('After the payment is canceled, it cannot be sent for settlment again.');
    $payment = $this->getEntity();
    $payment_method = $payment->getPaymentMethod();
    if ($payment_method->getCancelZeroAmount()) {
      $result .= ' ' . $this->t('The payment total amount will be set to 0 (existing line items will be kept for history, new line items with negative amount will be created).');
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $this->getEntity();
    /** @var \Drupal\payment_authnet\Plugin\Payment\Method\Authnet $payment_method */
    $payment_method = $payment->getPaymentMethod();
    $result = $payment_method->voidPayment();

    if ($result->isCompleted()) {
      $form_state->setRedirectUrl($payment->toUrl());
    }
    else {
      $form_state->setResponse($result->getCompletionResponse()->getResponse());
    }
  }

}
