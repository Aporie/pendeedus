<?php

namespace Drupal\payment_reference\Plugin\Field\FieldType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\currency\Entity\Currency;
use Drupal\payment\Element\PaymentLineItemsInput;
use Drupal\payment\Payment;
use Drupal\payment_reference\PaymentReference as PaymentReferenceServiceWrapper;

/**
 * Provides a configurable payment reference field.
 *
 * @FieldType(
 *   configurable = "true",
 *   default_formatter = "entity_reference_label",
 *   default_widget = "payment_reference",
 *   id = "payment_reference",
 *   label = @Translation("Payment reference"),
 *   list_class = "\Drupal\payment_reference\Plugin\Field\FieldType\PaymentReferenceItemList"
 * )
 */
class PaymentReference extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'payment',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return parent::defaultFieldSettings() + [
      'currency_code' => '',
      'line_items_data' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_storage_definition) {
    return [
      'columns' => [
        'target_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
      'foreign keys' => [
        'target_id' => [
          'table' => 'payment',
          'columns' => [
            'target_id' => 'id',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\currency\FormHelperInterface $form_helper */
    $form_helper = \Drupal::service('currency.form_helper');

    $element['#element_validate'] = [get_class() . '::fieldSettingsFormValidate'];
    $element['currency_code'] = [
      '#empty_value' => '',
      '#type' => 'select',
      '#title' => $this->t('Payment currency'),
      '#options' => $form_helper->getCurrencyOptions(),
      '#default_value' => $this->getSetting('currency_code'),
      '#required' => TRUE,
    ];
    $line_items = [];
    foreach ($this->getSetting('line_items_data') as $line_item_data) {
      $line_items[] = Payment::lineItemManager()->createInstance($line_item_data['plugin_id'], $line_item_data['plugin_configuration']);
    }
    $element['line_items'] = [
      '#type' => 'payment_line_items_input',
      '#title' => $this->t('Line items'),
      '#default_value' => $line_items,
      '#required' => TRUE,
      '#currency_code' => '',
    ];

    return $element;
  }

  /**
   * Implements #element_validate callback for self::fieldSettingsForm().
   */
  public static function fieldSettingsFormValidate(array $element, FormStateInterface $form_state) {
    $add_more_button_form_parents = array_merge($element['#array_parents'], ['line_items', 'add_more', 'add']);
    // Only set the field settings as a value when it is not the "Add more"
    // button that has been clicked.
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#array_parents'] != $add_more_button_form_parents) {
      $values = $form_state->getValues();
      $values = NestedArray::getValue($values, $element['#array_parents']);
      $line_items_data = [];
      foreach (PaymentLineItemsInput::getLineItems($element['line_items'], $form_state) as $line_item) {
        $line_items_data[] = [
          'plugin_id' => $line_item->getPluginId(),
          'plugin_configuration' => $line_item->getConfiguration(),
        ];
      }
      $value = [
        'currency_code' => $values['currency_code'],
        'line_items_data' => $line_items_data,
      ];
      $form_state->setValueForElement($element, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $entity_type_id = $this->getFieldDefinition()->getFieldStorageDefinition()->getTargetEntityTypeId();
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $current_entity */
    $current_entity = $this->getEntity();
    $unchanged_payment_id = NULL;
    if ($current_entity->id()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $unchanged_entity */
      $unchanged_entity = $entity_storage->loadUnchanged($current_entity->id());
      if ($unchanged_entity) {
        $unchanged_field = $unchanged_entity->get($this->getFieldDefinition()->getName());
        if (!$unchanged_field->isEmpty()) {
          $unchanged_payment_id = $unchanged_field->get($this->name)->get('target_id')->getValue();
        }
      }
    }
    $current_payment_id = $this->get('target_id')->getValue();

    // Only claim the payment if the payment ID in this field has changed since
    // the field's target entity was last saved or if the entity is new.
    if (!$current_entity->id() || $current_payment_id != $unchanged_payment_id) {
      $queue = PaymentReferenceServiceWrapper::queue();
      $acquisition_code = $queue->claimPayment($current_payment_id);
      if ($acquisition_code !== FALSE) {
        $queue->acquirePayment($current_payment_id, $acquisition_code);
      }
      else {
        $this->get('target_id')->setValue(0);
      }
    }
  }

}
