<?php

namespace Drupal\payment_reference_test;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Payment;
use Drupal\payment\Tests\Generate;
use Drupal\payment_reference\PaymentReference;

/**
 * Provides a form for testing the payment_reference element.
 */
class PaymentReferenceElement implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_reference_test_payment_reference_element';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $key = 'payment_reference_element_prototype_payment';
    if ($form_state->has($key)) {
      /** @var \Drupal\payment\Entity\PaymentInterface $prototype_payment */
      $prototype_payment = $form_state->get($key);
      $payment_type = $prototype_payment->getPaymentType();
    }
    else {
      $entity_type_id = 'user';
      $bundle = 'user';
      $field_name = 'foobarbaz';
      /** @var \Drupal\payment\Entity\PaymentInterface $prototype_payment */
      $prototype_payment = Payment::create(array(
        'bundle' => 'payment_reference',
      ));
      $prototype_payment->setCurrencyCode('EUR')
        ->setOwnerId(2)
        ->setLineItems(Generate::createPaymentLineItems());
      /** @var \Drupal\payment_reference\Plugin\Payment\Type\PaymentReference $payment_type */
      $payment_type = $prototype_payment->getPaymentType();
      $payment_type->setEntityTypeId($entity_type_id);
      $payment_type->setBundle($bundle);
      $payment_type->setFieldName($field_name);
      $form_state->set($key, $prototype_payment);
    }
    $form['payment_reference'] = array(
      '#plugin_selector_id' => 'payment_select_list',
      '#prototype_payment' => $prototype_payment,
      '#queue_category_id' => $payment_type->getEntityTypeId() . '.' . $payment_type->getBundle(). '.' . $payment_type->getFieldName(),
      '#queue_owner_id' => 2,
      '#required' => TRUE,
      '#title' => 'FooBarBaz',
      '#type' => 'payment_reference',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $payment_id = $form['payment_reference']['#value'];
    $acquisition_code = PaymentReference::queue()->claimPayment($payment_id);
    if ($acquisition_code && PaymentReference::queue()->acquirePayment($payment_id, $acquisition_code)) {
      \Drupal::state()->set('payment_reference_test_payment_reference_element', $payment_id);
    }
  }
}
