<?php

namespace Drupal\payment_authnet;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper class with functions for modifying the Class node add/edit form.
 */
class PaymentRefundFormAlter {

  use StringTranslationTrait;

  /**
   * Attaches "auto-fill" behaviors for the Room field.
   *
   * @param array $form
   *   Form array, as passed into hook_form_alter().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State object, as passed into hook_form_alter().
   */
  public function attachPartialRefunds(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = $payment->getCurrency();
    $line_items = $payment->getLineItems();
    $line_items_options = $line_items_prices = [];
    $refund_amount_id = Html::getId('refund-amount');
    $line_items_id = Html::getUniqueId('authnet-line-items');
    $form['authnet_line_items'] = [
      '#type' => 'details',
      '#title' => $this->t('Authorize.net Refund Options'),
      '#description' => $this->t('Set refund amount for each line item in particular. If total amount of line item is max, it will be removed from payment.'),
      '#tree' => TRUE,
      '#id' => $line_items_id,
      '#required' => TRUE,
    ];
    $index = 0;
    $authnet_refund_amount_max = $line_items_amount = $this->getRefundAmountMax($form_state);
    foreach ($line_items as $payment_line_item) {
      $amount = $currency->formatAmount($payment_line_item->getAmount());
      $quantity = $payment_line_item->getQuantity();
      $line_item_amount = min([
        $payment_line_item->getTotalAmount(),
        $line_items_amount,
      ]);
      $line_items_amount -= $line_item_amount;
      $total_amount = $currency->formatAmount($payment_line_item->getTotalAmount());
      $config = $payment_line_item->getConfiguration();

      $form['authnet_line_items'][$config['name']] = [
        '#type' => 'number',
        '#title' => $config['description'] . ' (' . $quantity . ' x ' . $amount . ' = ' . $total_amount . ')',
        '#title_display' => 'invisible',
        '#size' => 10,
        '#maxlength' => 15,
        '#field_prefix' => ++$index . '. ' . $currency->getSign(),
        '#field_suffix' => $config['description'] . ' (' . $quantity . ' x ' . $amount . ' = ' . $total_amount . ')',
        '#min' => count($line_items) > 1 ? '0.00' : '0.01',
        '#max' => bcadd(0, min([$authnet_refund_amount_max, $payment_line_item->getTotalAmount()]), 2),
        '#step' => '0.01',
        '#default_value' => bcadd(0, $line_item_amount, 2),
        '#required' => count($line_items) > 1 ? FALSE : TRUE,
      ];

      $line_items_options[$config['name']] = $config['description'] . ' (' . $quantity . ' x ' . $amount . ' = ' . $total_amount . ')';
    }
    $form['authnet_refund_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Total Refund Amount'),
      '#description' => $this->t('This is the total amount and must include tax, shipping, tips, and any other charges.'),
      '#description_display' => 'after',
      '#default_value' => $form_state->getValue('authnet_refund_amount', bcadd(0, $authnet_refund_amount_max, 2)),
      '#field_prefix' => $currency->getSign(),
      '#size' => 10,
      '#maxlength' => 15,
      '#id' => $refund_amount_id,
      '#max' => bcadd(0, $authnet_refund_amount_max, 2),
      '#min' => '0.01',
      '#step' => '0.01',
      '#disabled' => TRUE,
    ];
    $form['authnet_refund_amount']['#description'] .= $payment->getAmount() > $authnet_refund_amount_max ? ' ' . $this->t('Previous refund operations amount: @amount', [
      '@amount' => $currency->formatAmount($payment->getAmount() - $authnet_refund_amount_max),
    ]) : '';

    $form['#attached']['drupalSettings']['paymentAuthnet']['refundAmountId'] = $refund_amount_id;
    $form['#attached']['drupalSettings']['paymentAuthnet']['lineItemsId'] = $line_items_id;
    $form['#attached']['library'][] = 'payment_authnet/refund.calculate_amount';

    array_unshift($form['#validate'], [self::class, 'validateForm']);
    array_unshift($form['actions']['submit']['#submit'], [self::class, 'submitForm']);
  }

  /**
   * Gets max value of refund amount.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State object, as passed into hook_form_alter().
   */
  protected function getRefundAmountMax(FormStateInterface $form_state) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $form_state->getFormObject()->getEntity();
    $payment_method = $payment->getPaymentMethod();
    if ($payment_method->getCloneRefunded()) {
      $result = bcsub($payment->getAmount(), $payment_method->getConfiguration('refunded_amount', 0), 6);
    }
    else {
      $result = bcadd(0, $payment->getAmount(), 6);
    }
    return $result;
  }

  /**
   * Custom validation handler for refund form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State object, as passed into hook_form_alter().
   */
  public static function validateForm(array &$form, FormStateInterface $form_state) {
    if (array_sum($form_state->getValue('authnet_line_items')) <= 0) {
      $form_state->setErrorByName('authnet_line_items', t('Amount of at least one line item should be greater than 0.'));
    }
  }

  /**
   * Custom submission handler for refund form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State object, as passed into hook_form_alter().
   */
  public static function submitForm(array &$form, FormStateInterface $form_state) {
    $line_items = $form_state->getValue('authnet_line_items');
    $payment = $form_state->getFormObject()->getEntity();

    // Update line items accordingly before sending refund request.
    foreach ($line_items as $line_item_name => $amount) {
      if (empty($amount) || $amount < 0.01) {
        $payment->unsetLineItem($line_item_name);
      }
      elseif ($payment->getLineItem($line_item_name)->getTotalAmount() - $amount > 0.01) {
        $payment_line_item = $payment->getLineItem($line_item_name);
        $line_item_amount = bcdiv($amount, $payment_line_item->getQuantity(), 6);
        $payment_line_item->setAmount($line_item_amount);
        $payment->setLineItem($payment_line_item);
      }
    }
  }

}
