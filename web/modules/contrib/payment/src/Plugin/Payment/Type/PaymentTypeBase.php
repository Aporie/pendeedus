<?php

namespace Drupal\payment\Plugin\Payment\Type;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\PaymentAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines base payment type.
 */
abstract class PaymentTypeBase extends PluginBase implements ContainerFactoryPluginInterface, PaymentTypeInterface, ConfigurableInterface, DependentPluginInterface {

  use PaymentAwareTrait;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\payment\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EventDispatcherInterface $event_dispatcher) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('payment.event_dispatcher'));
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  function getResumeContextResponse() {
    $this->eventDispatcher->preResumeContext($this->getPayment());
    return $this->doGetResumeContextResponse();
  }

  /**
   * Performs the actual context resumption.
   *
   * @return \Drupal\payment\Response\ResponseInterface
   */
  abstract protected function doGetResumeContextResponse();

}
