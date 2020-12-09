<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\Status\PaymentStatusManager.
 */

namespace Drupal\payment\Plugin\Payment\Status;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin\Plugin\PluginOperationsProviderPluginManagerTrait;

/**
 * Manages discovery and instantiation of payment status plugins.
 *
 * @see \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface
 */
class PaymentStatusManager extends DefaultPluginManager implements PaymentStatusManagerInterface, FallbackPluginManagerInterface {

  use PluginOperationsProviderPluginManagerTrait;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    // The plugin ID. Set by the plugin system based on the top-level YAML key.
    'id' => NULL,
    // The plugin ID of the parent status (required).
    'parent_id' => NULL,
    // The human-readable plugin label (optional).
    'label' => NULL,
    // The human-readable plugin description (optional).
    'description' => NULL,
    // The name of the class that provides plugin operations. The class must
    // implement \Drupal\plugin\PluginOperationsProviderInterface.
    'operations_provider' => NULL,
    // The default plugin class name. Any class must implement
    // \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface.
    'class' => DefaultPaymentStatus::class,
  );

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class_resolver.
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   *   The string translator.
   */
  public function __construct(CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ClassResolverInterface $class_resolver, TranslationInterface $string_translation) {
    $this->alterInfo('payment_status');
    $this->setCacheBackend($cache_backend, 'payment_status', ['payment_status']);
    $this->classResolver = $class_resolver;
    $this->discovery = new YamlDiscovery('payment.status', $module_handler->getModuleDirectories());
    $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    $this->factory = new ContainerFactory($this, PaymentStatusInterface::class);
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'payment_unknown';
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    foreach (['description', 'label'] as $key) {
      if (isset($definition[$key])) {
        $definition[$key] = new TranslatableMarkup($definition[$key], [], [], $this->stringTranslation);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAncestors($plugin_id) {
    $definition = $this->getDefinition($plugin_id);
    if (isset($definition['parent_id'])) {
      $parent_id = $definition['parent_id'];
      return array_unique(array_merge(array($parent_id), $this->getAncestors($parent_id)));
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren($plugin_id) {
    $child_plugin_ids = [];
    foreach ($this->getDefinitions() as $definition) {
      if (isset($definition['parent_id']) && $definition['parent_id'] == $plugin_id) {
        $child_plugin_ids[] = $definition['id'];
      }
    }

    return $child_plugin_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendants($plugin_id) {
    $child_plugin_ids = $this->getChildren($plugin_id);
    $descendant_plugin_ids = $child_plugin_ids;
    foreach ($child_plugin_ids as $child_plugin_id) {
      $descendant_plugin_ids = array_merge($descendant_plugin_ids, $this->getDescendants($child_plugin_id));
    }

    return array_unique($descendant_plugin_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAncestor($plugin_id, $ancestor_plugin_id) {
    return in_array($ancestor_plugin_id, $this->getAncestors($plugin_id));
  }

  /**
   * {@inheritdoc}
   */
  public function isOrHasAncestor($plugin_id, $ancestor_plugin_id) {
    return $plugin_id == $ancestor_plugin_id || $this->hasAncestor($plugin_id, $ancestor_plugin_id);
  }

}
