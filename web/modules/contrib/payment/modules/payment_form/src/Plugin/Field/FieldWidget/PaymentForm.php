<?php

namespace Drupal\payment_form\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A payment configuration widget.
 *
 * @FieldWidget(
 *   field_types = {
 *     "payment_form"
 *   },
 *   id = "payment_form",
 *   label = @Translation("Line item configuration"),
 *   multiple_values = "true"
 * )
 */
class PaymentForm extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The payment line item manager.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface
   */
  protected $paymentLineItemManager;

  /**
   * Constructs a new instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param mixed[] $settings
   *   The widget settings.
   * @param array[] $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface $payment_line_item_manager
   *   The payment line item manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, TranslationInterface $string_translation, PaymentLineItemManagerInterface $payment_line_item_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->paymentLineItemManager = $payment_line_item_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('string_translation'), $container->get('plugin.manager.payment.line_item'));
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [$this->formatPlural(count($this->getSetting('line_items')), '1 line item.', '@count line items')];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#items'] = $items;
    $element['#process'][] = [$this, 'formElementProcess'];

    return $element;
  }

  /**
   * Implements form API #process callback.
   */
  public function formElementProcess(array $element, FormStateInterface $form_state, array $form) {
    $element['array_parents'] = [
      '#value' => $element['#array_parents'],
      '#type' => 'value',
    ];
    $line_items = [];
    foreach ($element['#items'] as $item) {
      if ($item->plugin_id) {
        $line_items[] = $this->paymentLineItemManager->createInstance($item->plugin_id, $item->plugin_configuration);
      }
    }
    $element['line_items'] = [
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      '#default_value' => $line_items,
      '#type' => 'payment_line_items_input',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $element = NestedArray::getValue($form, array_merge(array_slice($values['array_parents'], count($form['#array_parents'])), ['line_items']));

    $line_items_data = [];
    /** @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface $line_item */
    foreach ($element['#value'] as $line_item) {
      $line_items_data[] = [
        'plugin_id' => $line_item->getPluginId(),
        'plugin_configuration' => $line_item->getConfiguration(),
      ];
    }

    return $line_items_data;
  }

}
