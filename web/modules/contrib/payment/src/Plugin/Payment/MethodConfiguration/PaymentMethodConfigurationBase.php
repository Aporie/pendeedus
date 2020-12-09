<?php

/**
 * Contains \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase.
 */

namespace Drupal\payment\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base payment method configuration plugin.
 *
 * Plugins extending this class should provide a configuration schema that
 * extends
 * plugin.plugin_configuration.payment_method_configuration.payment_base.
 */
abstract class PaymentMethodConfigurationBase extends PluginBase implements PaymentMethodConfigurationInterface, ConfigurableInterface, DependentPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   * @param string $plugin_id
   * @param mixed[] $plugin_definition
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('string_translation'), $container->get('module_handler'));
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
    return array(
      'message_text' => '',
      'message_text_format' => 'plain_text',
    );
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
   * Sets payer message text.
   *
   * @param string $text
   *
   * @return \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface
   */
  public function setMessageText($text) {
    $this->configuration['message_text'] = $text;

    return $this;
  }

  /**
   * Gets the payer message text.
   *
   * @return string
   */
  public function getMessageText() {
    return $this->configuration['message_text'];
  }

  /**
   * Sets payer message text format.
   *
   * @param string $format
   *   The machine name of the text format the payer message is in.
   *
   * @return \Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface
   */
  public function setMessageTextFormat($format) {
    $this->configuration['message_text_format'] = $format;

    return $this;
  }

  /**
   * Gets the payer message text format.
   *
   * @return string
   */
  public function getMessageTextFormat() {
    return $this->configuration['message_text_format'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo Add a token overview, possibly when Token.module has been ported.
    $elements['message'] = array(
      '#tree' => TRUE,
      '#type' => 'textarea',
      '#title' => $this->t('Payment form message'),
      '#default_value' => $this->getMessageText(),
    );
    if ($this->moduleHandler->moduleExists('filter')) {
      $elements['message']['#type'] = 'text_format';
      $elements['message']['#format'] = $this->getMessageTextFormat();
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
    $values = $form_state->getValues();
    $message = NestedArray::getValue($values, $form['message']['#parents']);
    if ($this->moduleHandler->moduleExists('filter')) {
      $this->setMessageText($message['value']);
      $this->setMessageTextFormat($message['format']);
    }
    else {
      $this->setMessageText($message);
    }
  }
}
