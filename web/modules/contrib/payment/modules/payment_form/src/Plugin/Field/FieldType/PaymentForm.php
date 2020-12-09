<?php

/**
 * Contains \Drupal\payment_form\Plugin\field\field_type\PaymentForm.
 */

namespace Drupal\payment_form\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\currency\Entity\Currency;
use Drupal\payment\Plugin\Field\FieldType\PaymentAwarePluginCollectionItem;

/**
 * Defines a payment form field.
 *
 * @FieldType(
 *   default_widget = "payment_form",
 *   default_formatter = "payment_form",
 *   id = "payment_form",
 *   label = @Translation("Payment form"),
 *   plugin_type_id = "payment_line_item"
 * )
 */
class PaymentForm extends PaymentAwarePluginCollectionItem {

  /**
   * Definitions of the contained properties.
   *
   * @see static::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'currency_code' => 'XXX',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\currency\FormHelperInterface $form_helper */
    $form_helper = \Drupal::service('currency.form_helper');

    $element['currency_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment currency'),
      '#options' => $form_helper->getCurrencyOptions(),
      '#default_value' => $this->getSetting('currency_code'),
      '#required' => TRUE,
    ];

    return $element;
  }

}
