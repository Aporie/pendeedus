<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Method\PaymentMethodConfigurationOperationsProvider.
 */

namespace Drupal\payment\Plugin\Payment\Method;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin\PluginOperationsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides operations for payment methods based on config entities.
 */
abstract class PaymentMethodConfigurationOperationsProvider implements PluginOperationsProviderInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The payment method configuration list builder.
   *
   * @var \Drupal\Core\Entity\EntityListBuilderInterface
   */
  protected $paymentMethodConfigurationListBuilder;

  /**
   * The payment method configuration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentMethodConfigurationStorage;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   * @param \Drupal\Core\Entity\EntityStorageInterface $payment_method_configuration_storage
   *   The payment method configuration storage.
   * @param \Drupal\Core\Entity\EntityListBuilderInterface $payment_method_configuration_list_builder
   *   The payment method configuration list builder.
   */
  public function __construct(TranslationInterface $string_translation, RedirectDestinationInterface $redirect_destination, EntityStorageInterface $payment_method_configuration_storage, EntityListBuilderInterface $payment_method_configuration_list_builder) {
    $this->paymentMethodConfigurationListBuilder = $payment_method_configuration_list_builder;
    $this->paymentMethodConfigurationStorage = $payment_method_configuration_storage;
    $this->redirectDestination = $redirect_destination;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($container->get('string_translation'), $container->get('redirect.destination'), $entity_type_manager->getStorage('payment_method_configuration'), $entity_type_manager->getListBuilder('payment_method_configuration'));
  }

  /**
   * Gets the payment method configuration entity for this plugin.
   *
   * @param string $plugin_id
   *   This plugin's ID.
   *
   * @return \Drupal\payment\Entity\PaymentMethodConfigurationInterface
   */
  abstract protected function getPaymentMethodConfiguration($plugin_id);

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_id) {
    $payment_method_configuration_operations = $this->paymentMethodConfigurationListBuilder->getOperations($this->getPaymentMethodConfiguration($plugin_id));

    $titles = array(
      'edit' => $this->t('Edit configuration'),
      'delete' => $this->t('Delete configuration'),
      'enable' => $this->t('Enable configuration'),
      'disable' => $this->t('Disable configuration'),
    );
    $operations = [];
    foreach ($payment_method_configuration_operations as $name => $payment_method_configuration_operation) {
      if (array_key_exists($name, $titles)) {
        $operations[$name] = $payment_method_configuration_operation;
        $operations[$name]['title'] = $titles[$name];
        $operations[$name]['query']['destination'] = $this->redirectDestination->get();
      }
    }

    return $operations;
  }

}
