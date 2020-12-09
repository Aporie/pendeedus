<?php

namespace Drupal\payment_form\Plugin\Payment\Type;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginHelper;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the configuration form for the payment_form payment type plugin.
 */
class PaymentFormConfigurationForm extends ConfigFormBase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * The plugin selector plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $pluginSelectorType;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface $payment_method_manager
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $plugin_selector_type
   */
  public function __construct(ConfigFactoryInterface $config_factory, TranslationInterface $string_translation, PaymentMethodManagerInterface $payment_method_manager, PluginTypeInterface $plugin_selector_type) {
    parent::__construct($config_factory);
    $this->paymentMethodManager = $payment_method_manager;
    $this->pluginSelectorType = $plugin_selector_type;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = $container->get('plugin.plugin_type_manager');

    return new static(
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('plugin.manager.payment.method'),
      $plugin_type_manager->getPluginType('plugin_selector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_form_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['payment_form.payment_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('payment_form.payment_type');

    $form['plugin_selector'] = $this->getPluginSelector($form_state)->buildSelectorForm([], $form_state);

    $limit_allowed_plugins_id = Html::getUniqueId('limit_allowed_plugins');
    $form['limit_allowed_plugins'] = [
      '#default_value' => $config->get('limit_allowed_plugins'),
      '#id' => $limit_allowed_plugins_id,
      '#title' => $this->t('Limit allowed payment methods'),
      '#type' => 'checkbox',
    ];
    $allowed_plugin_ids = $config->get('allowed_plugin_ids');
    $options = [];
    foreach ($this->paymentMethodManager->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }
    $form['allowed_plugin_ids'] = [
      '#default_value' => $allowed_plugin_ids,
      '#multiple' => TRUE,
      '#options' => $options,
      '#states' => [
        'visible' => [
          '#' . $limit_allowed_plugins_id => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#title' => $this->t('Allowed payment methods'),
      '#type' => 'select',
    ];

    return $form + parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->getPluginSelector($form_state)->validateSelectorForm($form['plugin_selector'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin_selector = $this->getPluginSelector($form_state);
    $plugin_selector->submitSelectorForm($form['plugin_selector'], $form_state);
    $selected_plugin = $plugin_selector->getSelectedPlugin();
    $config = $this->config('payment_form.payment_type');
    $values = $form_state->getValues();
    $config->set('plugin_selector_id', $selected_plugin->getPluginId());
    if (PluginHelper::isConfigurable($selected_plugin)) {
      $selected_plugin_configuration = $selected_plugin->getConfiguration();
    }
    else {
      $selected_plugin_configuration = [];
    }
    $config->set('plugin_selector_configuration', $selected_plugin_configuration);
    $config->set('limit_allowed_plugins', $values['limit_allowed_plugins']);
    $config->set('allowed_plugin_ids', $values['allowed_plugin_ids']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the plugin selector.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   */
  protected function getPluginSelector(FormStateInterface $form_state) {
    $config = $this->config('payment_form.payment_type');
    if ($form_state->has('plugin_selector')) {
      $plugin_selector = $form_state->get('plugin_selector');
    }
    else {
      $plugin_selector_manager = $this->pluginSelectorType->getPluginManager();
      $plugin_selector = $plugin_selector_manager->createInstance('payment_radios');
      $plugin_selector->setSelectablePluginType($this->pluginSelectorType);
      $plugin_selector->setLabel($this->t('Payment method selector'));
      $plugin_selector->setRequired();
      $plugin_selector->setSelectedPlugin($plugin_selector_manager->createInstance($config->get('plugin_selector_id')));
      $form_state->set('plugin_selector', $plugin_selector);
    }

    return $plugin_selector;
  }

}
