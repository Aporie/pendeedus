<?php

namespace Drupal\payment_form\Entity\Payment;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentExecutionPaymentMethodManager;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginDiscovery\LimitedPluginDiscoveryDecorator;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment form.
 */
class PaymentForm extends ContentEntityForm {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The payment method plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $paymentMethodType;

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface
   */
  protected $pluginSelectorManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = $container->get('plugin.plugin_type_manager');

    /** @var static $form */
    $form = parent::create($container);

    $form->currentUser = $container->get('current_user');
    $form->paymentMethodType = $plugin_type_manager->getPluginType('payment_method');
    $form->pluginSelectorManager = $container->get('plugin.manager.plugin.plugin_selector');
    $form->urlGenerator = $container->get('url_generator');
    $form->stringTranslation = $container->get('string_translation');
    return $form;
  }

  /**
   * Sets the plugin selector manager.
   *
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface $plugin_selector_manager
   *   The plugin selector manager.
   */
  public function setPluginSelectorManager(PluginSelectorManagerInterface $plugin_selector_manager) {
    $this->pluginSelectorManager = $plugin_selector_manager;
  }

  /**
   * Sets the payment method plugin type.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $payment_method_type
   *   The plugin type.
   */
  public function setPaymentMethodPluginType(PluginTypeInterface $payment_method_type) {
    $this->paymentMethodType = $payment_method_type;
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  protected function currentUser() {
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $payment = $this->getEntity();

    $form['line_items'] = [
      '#payment_line_items' => $payment,
      '#type' => 'payment_line_items_display',
    ];
    $form['payment_method'] = $this->getPluginSelector($form_state)->buildSelectorForm([], $form_state);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->getPluginSelector($form_state)->validateSelectorForm($form['payment_method'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $this->getEntity();
    $plugin_selector = $this->getPluginSelector($form_state);
    $plugin_selector->submitSelectorForm($form['payment_method'], $form_state);
    $payment->setPaymentMethod($plugin_selector->getSelectedPlugin());
    $payment->save();
    $result = $payment->execute();
    if (!$result->isCompleted()) {
      $form_state->setResponse($result->getCompletionResponse()->getResponse());
    }
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Only use the existing submit action.
    $actions = parent::actions($form, $form_state);
    $actions = [
      'submit' => $actions['submit'],
    ];
    $actions['submit']['#value'] = $this->t('Pay');
    $actions['submit']['#disabled'] = count($this->getPaymentMethodManager()->getDefinitions()) == 0;

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Remove this override once https://drupal.org/node/2409143 has been fixed.
    $this->getFormDisplay($form_state)
      ->extractFormValues($entity, $form, $form_state);
  }

  /**
   * Gets the plugin selector.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   */
  protected function getPluginSelector(FormStateInterface $form_state) {
    if ($form_state->has('plugin_selector')) {
      $plugin_selector = $form_state->get('plugin_selector');
    }
    else {
      $config = $this->config('payment_form.payment_type');
      $plugin_selector_id = $config->get('plugin_selector_id');
      $plugin_selector = $this->pluginSelectorManager->createInstance($plugin_selector_id);
      $plugin_selector->setSelectablePluginType($this->paymentMethodType);
      $plugin_selector->setSelectablePluginDiscovery($this->getPaymentMethodManager());
      $plugin_selector->setSelectablePluginFactory($this->getPaymentMethodManager());
      $plugin_selector->setRequired();
      $plugin_selector->setLabel($this->t('Payment method'));
      $form_state->set('plugin_selector', $plugin_selector);
    }

    return $plugin_selector;
  }

  /**
   * Gets the payment method manager.
   *
   * @return \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface
   */
  protected function getPaymentMethodManager() {
    $config = $this->config('payment_form.payment_type');
    $limit_allowed_plugins = $config->get('limit_allowed_plugins');
    $payment_method_discovery = $this->paymentMethodType->getPluginManager();
    if ($limit_allowed_plugins) {
      $allowed_plugin_ids = $config->get('allowed_plugin_ids');
      $payment_method_discovery = new LimitedPluginDiscoveryDecorator($payment_method_discovery);
      $payment_method_discovery->setDiscoveryLimit($allowed_plugin_ids);
    }
    $payment_method_manager = new PaymentExecutionPaymentMethodManager($this->getEntity(), $this->currentUser, $this->paymentMethodType->getPluginManager(), $payment_method_discovery);

    return $payment_method_manager;
  }

}
