<?php

namespace Drupal\payment\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets a status on a payment.
 *
 * @Action(
 *   id = "payment_set_status",
 *   label = @Translation("Set payment status"),
 *   type = "payment"
 * )
 */
class SetStatus extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The payment status type.
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
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface $plugin_selector_manager
   *   The plugin selector manager.
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $payment_status_type
   *   The payment status type.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslationInterface $string_translation, PluginSelectorManagerInterface $plugin_selector_manager, PluginTypeInterface $payment_status_type) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginSelectorManager = $plugin_selector_manager;
    $this->paymentStatusType = $payment_status_type;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = $container->get('plugin.plugin_type_manager');

    return new static($configuration, $plugin_id, $plugin_definition, $container->get('string_translation'), $container->get('plugin.manager.plugin.plugin_selector'), $plugin_type_manager->getPluginType('payment_status'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(PaymentInterface $payment = NULL) {
    if ($payment) {
      $status = $this->paymentStatusType->getPluginManager()->createInstance($this->configuration['payment_status_plugin_id']);
      $payment->setPaymentStatus($status);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'payment_status_plugin_id' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['payment_status_plugin_id'] = $this->getPluginSelector($form_state)->buildSelectorForm([], $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->getPluginSelector($form_state)->validateSelectorForm($form['payment_status_plugin_id'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->getPluginSelector($form_state)->submitSelectorForm($form['payment_status_plugin_id'], $form_state);
    $this->configuration['payment_status_plugin_id'] = $this->getPluginSelector($form_state)->getSelectedPlugin()->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function access($payment, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($payment instanceof PaymentInterface) {
      return $payment->access('update', $account, $return_as_object);
    }
    $access = AccessResult::forbidden();
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Gets the plugin selector.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   */
  protected function getPluginSelector(FormStateInterface $form_state) {
    if (!$form_state->has('plugin_selector')) {
      $plugin_selector = $this->pluginSelectorManager->createInstance('payment_select_list');
      $plugin_selector->setSelectablePluginType($this->paymentStatusType);
      $plugin_selector->setRequired();
      $plugin_selector->setLabel($this->t('Payment status'));
      $plugin_selector->setCollectPluginConfiguration(FALSE);
      $form_state->set('plugin_selector', $plugin_selector);
    }

    return $form_state->get('plugin_selector');
  }

}
