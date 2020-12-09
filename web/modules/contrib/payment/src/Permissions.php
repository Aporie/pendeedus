<?php

namespace Drupal\payment;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions
 */
class Permissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The payment method configuration configuration manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $paymentMethodConfigurationManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Component\Plugin\PluginManagerInterface $payment_method_configuration_manager
   */
  public function __construct(TranslationInterface $string_translation, PluginManagerInterface $payment_method_configuration_manager) {
    $this->paymentMethodConfigurationManager = $payment_method_configuration_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('plugin.manager.payment.method_configuration'));
  }

  /**
   * Returns permissions.
   *
   * @return array[]
   *   The structure is the same as that of *.permissions.yml files.
   */
  public function getPermissions() {
    $permissions = [];
    $definitions = $this->paymentMethodConfigurationManager->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      $permissions['payment.payment_method_configuration.create.' . $plugin_id] = array(
        'title' => $this->t('Create %plugin_label payment method configurations', array(
          '%plugin_label' => $definition['label'],
        )),
      );
    }

    return $permissions;
  }

}
