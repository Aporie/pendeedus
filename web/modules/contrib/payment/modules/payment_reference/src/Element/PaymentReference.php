<?php

namespace Drupal\payment_reference\Element;

use Drupal\Component\Utility\Random;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\payment\Element\PaymentReferenceBase;
use Drupal\payment\QueueInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a payment reference element.
 *
 * @FormElement("payment_reference")
 */
class PaymentReference extends PaymentReferenceBase {

  /**
   * The payment queue.
   *
   * @var \Drupal\payment\QueueInterface
   */
  protected $paymentQueue;

  /**
   * Creates a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Entity\EntityStorageInterface $payment_storage
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface $plugin_selector_manager
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $payment_method_type
   * @param \Drupal\payment\QueueInterface $payment_queue
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, EntityStorageInterface $payment_storage, TranslationInterface $string_translation, DateFormatter $date_formatter, LinkGeneratorInterface $link_generator, RendererInterface $renderer, AccountInterface $current_user, PluginSelectorManagerInterface $plugin_selector_manager, PluginTypeInterface $payment_method_type, Random $random, QueueInterface $payment_queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request_stack, $payment_storage, $string_translation, $date_formatter, $link_generator, $renderer, $current_user, $plugin_selector_manager, $payment_method_type, $random);
    $this->paymentQueue = $payment_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = $container->get('plugin.plugin_type_manager');

    return new static($configuration, $plugin_id, $plugin_definition, $container->get('request_stack'), $entity_type_manager->getStorage('payment'), $container->get('string_translation'), $container->get('date.formatter'), $container->get('link_generator'), $container->get('renderer'), $container->get('current_user'), $container->get('plugin.manager.plugin.plugin_selector'), $plugin_type_manager->getPluginType('payment_method'), new Random(), $container->get('payment_reference.queue'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getPaymentQueue() {
    return $this->paymentQueue;
  }

}
