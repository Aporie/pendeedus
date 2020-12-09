<?php

namespace Drupal\payment_test;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Payment;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentLineItemPaymentBasicFormElements implements ContainerInjectionInterface, FormInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_test-payment-line_item-payment_basic';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($form_state->has('payment_line_item')) {
      $line_item = $form_state->get('payment_line_item');
    }
    else {
      $line_item = Payment::lineItemManager()->createInstance('payment_basic');
      $form_state->set('payment_line_item', $line_item);
    }
    $form['line_item'] = $line_item->buildConfigurationForm([], $form_state);
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
    /** @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface $line_item */
    $line_item = $form_state->get('payment_line_item');
    $line_item->validateConfigurationForm($form['line_item'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface $line_item */
    $line_item = $form_state->get('payment_line_item');
    $line_item->submitConfigurationForm($form['line_item'], $form_state);
    $form_state->setRedirect('user.login');
  }
}
