<?php

namespace Drupal\payment\Plugin\Payment\Status;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\payment\PaymentAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base payment status.
 *
 * Plugins extending this class should provide a configuration schema that
 * extends plugin.plugin_configuration.payment_status.payment_base.
 */
abstract class PaymentStatusBase extends PluginBase implements ContainerFactoryPluginInterface, PaymentStatusInterface, PluginFormInterface, ConfigurableInterface {

  use PaymentAwareTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The default datetime.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $defaultDateTime;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The payment status plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface $payment_status_manager
   * @param \Drupal\Core\Datetime\DrupalDateTime $default_datetime
   *   The default datetime of the new status.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, PaymentStatusManagerInterface $payment_status_manager, DrupalDateTime $default_datetime) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->defaultDateTime = $default_datetime;
    $this->moduleHandler = $module_handler;
    $this->paymentStatusManager = $payment_status_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('module_handler'), $container->get('plugin.manager.payment.status'), new DrupalDateTime());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'created' => time(),
      'id' => 0,
    ];
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
  public function setCreated($created) {
    $this->configuration['created'] = $created;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {
    return $this->configuration['created'];
  }

  /**
   * {@inheritdoc}
   */
  function getAncestors(){
    return $this->paymentStatusManager->getAncestors($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren() {
    return $this->paymentStatusManager->getChildren($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  function getDescendants() {
    return $this->paymentStatusManager->getDescendants($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  function hasAncestor($plugin_id) {
    return $this->paymentStatusManager->hasAncestor($this->getPluginId(), $plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  function isOrHasAncestor($plugin_id) {
    return $this->paymentStatusManager->isOrHasAncestor($this->getPluginId(), $plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($this->moduleHandler->moduleExists('datetime')) {
      $elements['created'] = [
        '#default_value' => $this->defaultDateTime,
        '#required' => TRUE,
        '#title' => $this->t('Date and time'),
        '#type' => 'datetime',
      ];
    }
    else {
      $elements['created'] = [
        '#default_value' => $this->defaultDateTime,
        '#type' => 'value',
      ];
      if ($this->currentUser->hasPermission('administer modules')) {
        $elements['created_message'] = [
          '#type' => 'markup',
          '#markup' => $this->t('Enable the <a href="@url">Datetime</a> module to set the date and time of the new payment status.',
            [
              '@url' => new Url('system.modules_list', [], [
                  'fragment' => 'module-datetime',
                ])
            ]),
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }


}
