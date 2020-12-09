<?php

/**
 * Contains \Drupal\payment_form\Plugin\Payment\Type\PaymentForm.
 */

namespace Drupal\payment_form\Plugin\Payment\Type;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeBase;
use Drupal\payment\Response\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The payment form field payment type.
 *
 * @PaymentType(
 *   configuration_form = "\Drupal\payment_form\Plugin\Payment\Type\PaymentFormConfigurationForm",
 *   id = "payment_form",
 *   label = @Translation("Payment form field")
 * )
 */
class PaymentForm extends PaymentTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\payment\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityFieldManagerInterface $entity_field_manager, TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher);
    $this->entityFieldManager = $entity_field_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('payment.event_dispatcher'),
      $container->get('entity_field.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'bundle' => NULL,
      'destination_url' => NULL,
      'entity_type_id' => NULL,
      'field_name' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetResumeContextResponse() {
    return new Response(Url::fromUri($this->getDestinationUrl()));
  }

  /**
   * {@inheritdoc}
   */
  public function resumeContextAccess(AccountInterface $account) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentDescription() {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->getEntityTypeId(), $this->getBundle());

    return isset($field_definitions[$this->getFieldName()]) ? $field_definitions[$this->getFieldName()]->getLabel() : new TranslatableMarkup('Unavailable');
  }

  /**
   * Sets the ID of the entity type the payment was made for.
   *
   * @param string $entity_type_id
   *
   * @return $this
   */
  public function setEntityTypeId($entity_type_id) {
    $this->configuration['entity_type_id'] = $entity_type_id;

    return $this;
  }

  /**
   * Gets the ID of the entity type the payment was made for.
   *
   * @return string
   */
  public function getEntityTypeId() {
    return $this->configuration['entity_type_id'];
  }

  /**
   * Sets the bundle of the entity the payment was made for.
   *
   * @param string $bundle
   *
   * @return $this
   */
  public function setBundle($bundle) {
    $this->configuration['bundle'] = $bundle;

    return $this;
  }

  /**
   * Gets the bundle of the entity the payment was made for.
   *
   * @return string
   */
  public function getBundle() {
    return $this->configuration['bundle'];
  }

  /**
   * Sets the name of the field the payment was made for.
   *
   * @param string $field_name
   *
   * @return $this
   */
  public function setFieldName($field_name) {
    $this->configuration['field_name'] = $field_name;

    return $this;
  }

  /**
   * Gets the name of the field the payment was made for.
   *
   * @return string
   */
  public function getFieldName() {
    return $this->configuration['field_name'];
  }

  /**
   * Sets the URL the user should be redirected to upon resuming the context.
   *
   * @param string $url
   *   The destination URL.
   *
   * @return $this
   */
  public function setDestinationUrl($url) {
    $this->configuration['destination_url'] = $url;

    return $this;
  }

  /**
   * Gets the URL the user should be redirected to upon resuming the context.
   *
   * @return string
   *   The destination URL.
   */
  public function getDestinationUrl() {
    return $this->configuration['destination_url'];
  }

}
