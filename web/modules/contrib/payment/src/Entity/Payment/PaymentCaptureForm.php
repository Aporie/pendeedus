<?php

namespace Drupal\payment\Entity\Payment;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment capture form.
 */
class PaymentCaptureForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $form */
    $form = parent::create($container);
    $form->stringTranslation = $container->get('string_translation');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to capture payment #@payment_id?', array(
      '@payment_id' => $this->getEntity()->id(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Capture');
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
    /** @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodCapturePaymentInterface $payment_method */
    $payment_method = $payment->getPaymentMethod();
    $result = $payment_method->capturePayment();

    if ($result->isCompleted()) {
      $form_state->setRedirectUrl($payment->toUrl());
    }
    else {
      $form_state->setResponse($result->getCompletionResponse()->getResponse());
    }
  }

}
