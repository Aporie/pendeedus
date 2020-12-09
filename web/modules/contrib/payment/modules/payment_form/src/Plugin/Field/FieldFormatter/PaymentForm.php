<?php

namespace Drupal\payment_form\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Formats payment form fields.
 *
 * @FieldFormatter(
 *   id = "payment_form",
 *   label = @Translation("Payment form"),
 *   field_types = {
 *     "payment_form",
 *   }
 * )
 */
class PaymentForm extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The payment line item manager.
   *
   * @var \Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemManagerInterface
   */
  protected $paymentLineItemManager;

  /**
   * The payment storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentStorage;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RequestStack $request_stack, EntityFormBuilderInterface $entity_form_builder, EntityStorageInterface $payment_storage, PaymentLineItemManagerInterface $payment_line_item_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityFormBuilder = $entity_form_builder;
    $this->paymentLineItemManager = $payment_line_item_manager;
    $this->paymentStorage = $payment_storage;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('request_stack'),
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager')->getStorage('payment'),
      $container->get('plugin.manager.payment.line_item')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity_type_id = $items->getEntity()->getEntityTypeId();
    $bundle = $items->getEntity()->bundle();
    $field_name = $this->fieldDefinition->getName();
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $this->paymentStorage->create([
      'bundle' => 'payment_form',
    ]);
    $payment->setCurrencyCode($this->fieldDefinition->getSetting('currency_code'));
    /** @var \Drupal\payment_form\Plugin\Payment\Type\PaymentForm $payment_type */
    $payment_type = $payment->getPaymentType();
    $payment_type->setDestinationUrl($this->requestStack->getCurrentRequest()->getUri());
    $payment_type->setEntityTypeId($entity_type_id);
    $payment_type->setBundle($bundle);
    $payment_type->setFieldName($field_name);
    foreach ($items as $item) {
      /** @var \Drupal\payment_form\Plugin\Field\FieldType\PaymentForm $item */
      $plugin_id = $item->get('plugin_id')->getValue();
      if ($plugin_id) {
        $payment->setLineItem($this->paymentLineItemManager->createInstance($plugin_id, $item->get('plugin_configuration')->getValue()));
      }
    }

    return $this->entityFormBuilder->getForm($payment, 'payment_form');
  }

}
