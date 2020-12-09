<?php

namespace Drupal\payment_authnet\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase;
use Drupal\payment_authnet\Entity\AuthnetProfile;

/**
 * Provides the configuration for the payment_authnet payment method plugin.
 *
 * Plugins extending this class should provide a configuration schema that
 * extends
 * plugin.plugin_configuration.payment_method_configuration.payment_basic.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("A payment method enabling payment through Authorize.net."),
 *   id = "payment_authnet",
 *   label = @Translation("Authorize.net")
 * )
 */
class Authnet extends PaymentMethodConfigurationBase implements ContainerFactoryPluginInterface {

  /**
   * The payment status plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $paymentStatusType;

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface
   */
  protected $pluginSelectorManager;

  /**
   * The list of Authnet Profiles.
   *
   * @var array
   */
  protected $authnetProfiles;

  /**
   * The list of selectors used in a form with configuration.
   *
   * @var array
   */
  protected $selectors;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface $plugin_selector_manager
   *   The plugin selector manager.
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $payment_status_type
   *   The payment status plugin type.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler, PluginSelectorManagerInterface $plugin_selector_manager, PluginTypeInterface $payment_status_type) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $string_translation, $module_handler);
    $this->paymentStatusType = $payment_status_type;
    $this->pluginSelectorManager = $plugin_selector_manager;
    $this->authnetProfiles = AuthnetProfile::loadMultiple();
    $this->setSelectors();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = $container->get('plugin.plugin_type_manager');

    return new static($configuration, $plugin_id, $plugin_definition, $container->get('string_translation'), $container->get('module_handler'), $container->get('plugin.manager.plugin.plugin_selector'), $plugin_type_manager->getPluginType('payment_status'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'brand_label' => '',
      'execute_status_id' => 'payment_authorized',
      'cancel_status_id' => 'payment_cancelled',
      'cancel_zero_amount' => TRUE,
      'capture' => TRUE,
      'capture_status_id' => 'payment_success',
      'refund' => TRUE,
      'refund_status_id' => 'payment_refunded',
      'clone_refunded' => FALSE,
      'partial_refund' => TRUE,
      'partial_refund_status_id' => 'payment_partially_refunded',
    ];
  }

  /**
   * Sets selectors array.
   */
  private function setSelectors() {
    $this->selectors = [];
    $this->selectors['execute'] = [
      '#title' => $this->t('Payment execution status'),
      '#description' => $this->t('The status to set payments to after being executed by this payment method.'),
      '#parents' => ['plugin_form', 'execute', 'execute_status'],
      '#tab_title' => $this->t('Execution'),
    ];
    $this->selectors['cancel'] = [
      '#title' => $this->t('Payment cancel status'),
      '#description' => $this->t('The status to set payments to after being cancelled by this payment method.'),
      '#parents' => ['plugin_form', 'cancel', 'cancel_status'],
      '#tab_title' => $this->t('Cancel'),
    ];
    $parents = ['plugin_form', 'capture', 'plugin_form', 'capture_status'];
    $this->selectors['capture'] = [
      '#title' => $this->t('Payment capture status'),
      '#description' => $this->t('The status to set payments to after being captured by this payment method.'),
      '#parents' => $parents,
      '#tab_title' => $this->t('Capture'),
    ];
    $this->selectors['refund'] = [
      '#title' => $this->t('Payment refund status'),
      '#description' => $this->t('The status to set payments to after being refunded by this payment method.'),
      '#parents' => ['plugin_form', 'refund', 'plugin_form', 'refund_status'],
      '#tab_title' => $this->t('Refund'),
    ];
    $this->selectors['partial_refund'] = [
      '#title' => $this->t('Payment partial refund status'),
      '#description' => $this->t('The status to set payments to after being partially refunded by this payment method.'),
      '#parents' => ['plugin_form', 'refund', 'partial_refund', 'status'],
    ];
  }

  /**
   * Get variable from configuration array.
   *
   * @param string $name
   *   Configuration property name to search for.
   */
  public function __get($name) {
    return isset($this->configuration[$name]) ? $this->configuration[$name] : NULL;
  }

  /**
   * Set value for property in configuration array.
   *
   * @param string $name
   *   Configuration property name to set.
   * @param mixed $value
   *   Configuration property value to set.
   */
  public function __set($name, $value) {
    $this->configuration[$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['plugin_form'] = [
      '#process' => [[$this, 'processBuildConfigurationForm']],
      '#type' => 'container',
    ];

    return $form;
  }

  /**
   * Implements a form API #process callback.
   */
  public function processBuildConfigurationForm(array &$element, FormStateInterface $form_state, array &$form) {
    $element['brand_label'] = [
      '#default_value' => $this->brand_label,
      '#description' => $this->t('The label that payers will see when choosing a payment method. Defaults to the payment method label.'),
      '#title' => $this->t('Brand label'),
      '#type' => 'textfield',
    ];

    $this->addProfileSelector($element, $form_state);

    $element['workflow'] = ['#type' => 'vertical_tabs'];
    $this->addVerticalTabs($element);
    $element['execute']['execute_status'] = $this->getPaymentStatusSelector($form_state, 'execute')->buildSelectorForm([], $form_state);
    $element['cancel']['cancel_status'] = $this->getPaymentStatusSelector($form_state, 'cancel')->buildSelectorForm([], $form_state);
    $element['cancel']['cancel_zero_amount'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Zero Amount'),
      '#description' => $this->t('If enabled, create existing line items will be cloned with negative amount in order to make total Amount equal 0.'),
      '#default_value' => $this->cancel_zero_amount,
    ];
    $capture_id = Html::getUniqueId('capture');
    $element['capture']['capture'] = [
      '#id' => $capture_id,
      '#type' => 'checkbox',
      '#title' => $this->t('Add an additional capture step after payments have been executed.'),
      '#default_value' => $this->capture,
    ];
    $this->preparePluginForm($element, 'capture', $form_state);

    $this->addRefundVerticalTab($element, $form_state);
    return $element;
  }

  /**
   * Helper method to create profile selector element.
   *
   * @param array $element
   *   Plugin form element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  private function addProfileSelector(array &$element, FormStateInterface $form_state) {
    $options = ['' => $this->t('- Select a profile -')];
    foreach ($this->authnetProfiles as $id => $authnet_profile) {
      $options[$id] = $authnet_profile->label();
    }
    $profile_ids = array_keys($this->authnetProfiles);

    $element['profile'] = [
      '#default_value' => $this->profile ?: reset($profile_ids),
      '#description' => $this->t('The Authorize.net profile that will be used to connect to Authorize.net.'),
      '#title' => $this->t('Authorize.net profile'),
      '#type' => 'select',
      '#options' => $options,
      '#required' => TRUE,
    ];
  }

  /**
   * Helper method to create refund vertical tab.
   *
   * @param array $element
   *   Plugin form element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  private function addRefundVerticalTab(array &$element, FormStateInterface $form_state) {
    $refund_id = Html::getUniqueId('refund');
    $element['refund']['refund'] = [
      '#id' => $refund_id,
      '#type' => 'checkbox',
      '#title' => $this->t('Add an additional refund step after payments have been executed.'),
      '#default_value' => $this->refund,
    ];
    $this->preparePluginForm($element, 'refund', $form_state);
    $element['refund']['clone_refunded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create a new payment entity for refund/partial refund operations.'),
      '#description' => $this->t('Amount for such payments will be a negative number.'),
      '#default_value' => $this->clone_refunded,
    ];
    $element['refund']['clone_refunded']['#states']['visible']['#' . $refund_id]['checked'] = TRUE;

    $partial_refund_id = Html::getUniqueId('partial_refund');
    $element['refund']['partial_refund'] = ['#type' => 'container'];
    $element['refund']['partial_refund']['#states']['visible']['#' . $refund_id]['checked'] = TRUE;

    $part_refund_container = &$element['refund']['partial_refund'];
    $part_refund_container['partial_refund'] = [
      '#id' => $partial_refund_id,
      '#type' => 'checkbox',
      '#title' => $this->t('Allow partial refunds.'),
      '#default_value' => $this->partial_refund,
    ];
    $part_refund_container['status'] = $this->getPaymentStatusSelector($form_state, 'partial_refund')->buildSelectorForm([], $form_state);
    $part_refund_container['status']['container']['#states']['visible']['#' . $partial_refund_id]['checked'] = TRUE;
  }

  /**
   * Helper method to prepare necessary containers for capture/cancel tabs.
   *
   * @param array $element
   *   Form plugin element array.
   * @param string $type
   *   Selector type: execute, cancel, capture, refund or partial_refund.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  private function preparePluginForm(array &$element, $type, FormStateInterface $form_state) {
    $html_id = isset($element[$type][$type]['#id']) ? $element[$type][$type]['#id'] : Html::getUniqueId($type);
    $element[$type]['plugin_form'] = ['#type' => 'container'];
    $element[$type]['plugin_form']['#states']['visible']['#' . $html_id]['checked'] = TRUE;
    $element[$type]['plugin_form'][$type . '_status'] = $this->getPaymentStatusSelector($form_state, $type)->buildSelectorForm([], $form_state);
  }

  /**
   * Prepares vertical tabs for method workflow.
   *
   * @param array $element
   *   Form array reference.
   */
  protected function addVerticalTabs(array &$element) {
    $workflow_group = implode('][', array_merge($element['#parents'], ['workflow']));
    foreach ($this->selectors as $name => $selector_data) {
      if (isset($selector_data['#tab_title'])) {
        $element[$name] = [
          '#group' => $workflow_group,
          '#type' => 'details',
          '#title' => $selector_data['#tab_title'],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->selectors as $type => $selector_data) {
      $element = &NestedArray::getValue($form, $selector_data['#parents']);
      $this->getPaymentStatusSelector($form_state, $type)->validateSelectorForm($element, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->selectors as $type => $selector_data) {
      $element = &NestedArray::getValue($form, $selector_data['#parents']);
      $this->getPaymentStatusSelector($form_state, $type)->submitSelectorForm($element, $form_state);
      $this->{$type . '_status_id'} = $this->getPaymentStatusSelector($form_state, $type)->getSelectedPlugin()->getPluginId();
    }

    $parents = $form['plugin_form']['brand_label']['#parents'];
    array_pop($parents);
    $form_state_values = $form_state->getValues();
    $values = NestedArray::getValue($form_state_values, $parents);
    $this->cancel_zero_amount = $values['cancel']['cancel_zero_amount'];
    $this->capture = $values['capture']['capture'];
    $this->refund = $values['refund']['refund'];
    $this->clone_refunded = $values['refund']['clone_refunded'];
    $this->partial_refund = $values['refund']['refund'] && $values['refund']['partial_refund']['partial_refund'] ? 1 : 0;
    $this->brand_label = $values['brand_label'];
    $this->profile = $values['profile'];
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Gets the payment status selector.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State object.
   * @param string $type
   *   Selector type.
   *
   * @return \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   *   Payment status selector.
   */
  protected function getPaymentStatusSelector(FormStateInterface $form_state, $type) {
    if (empty($this->selectors[$type])) {
      throw new InvalidArgumentException($this->t('The type @type is invalid.', [
        '@type' => $type,
      ]));
    }
    $key = 'payment_status_selector_' . $type;
    if ($form_state->has($key)) {
      $plugin_selector = $form_state->get($key);
    }
    else {
      $plugin_selector = $this->pluginSelectorManager->createInstance('payment_select_list');
      $plugin_selector->setSelectablePluginType($this->paymentStatusType);
      $plugin_selector->setRequired(TRUE);
      $plugin_selector->setCollectPluginConfiguration(FALSE);
      $plugin_selector->setSelectedPlugin($this->paymentStatusType->getPluginManager()->createInstance($this->{$type . '_status_id'}));
      $plugin_selector->setLabel($this->selectors[$type]['#title']);
      $plugin_selector->setDescription($this->selectors[$type]['#description']);
      $form_state->set($key, $plugin_selector);
    }

    return $plugin_selector;
  }

}
