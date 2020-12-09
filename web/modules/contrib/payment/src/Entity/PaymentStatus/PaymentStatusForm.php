<?php

namespace Drupal\payment\Entity\PaymentStatus;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment status add/edit form.
 */
class PaymentStatusForm extends EntityForm {

  /**
   * The payment status storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentStatusStorage;

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface
   */
  protected $pluginSelectorManager;

  /**
   * The payment status plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $paymentStatusPluginType;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Entity\EntityStorageInterface $payment_status_storage
   *   The payment status storage.
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface $plugin_selector_manager
   *   The plugin selector manager.
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $payment_status_plugin_type
   *   The payment status plugin type.
   */
  public function __construct(TranslationInterface $string_translation, EntityStorageInterface $payment_status_storage, PluginSelectorManagerInterface $plugin_selector_manager, PluginTypeInterface $payment_status_plugin_type) {
    $this->paymentStatusStorage = $payment_status_storage;
    $this->pluginSelectorManager = $plugin_selector_manager;
    $this->paymentStatusPluginType = $payment_status_plugin_type;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = $container->get('plugin.plugin_type_manager');

    return new static($container->get('string_translation'), $entity_type_manager->getStorage('payment_status'), $container->get('plugin.manager.plugin.plugin_selector'), $plugin_type_manager->getPluginType('payment_status'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Entity\PaymentStatusInterface $payment_status */
    $payment_status = $this->getEntity();
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $payment_status->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#default_value' => $payment_status->id(),
      '#disabled' => !$payment_status->isNew(),
      '#machine_name' => array(
        'source' => array('label'),
        'exists' => array($this, 'PaymentStatusIdExists'),
      ),
      '#maxlength' => 255,
      '#type' => 'machine_name',
      '#required' => TRUE,
    );
    $form['parent_id'] = $this->getParentPaymentStatusSelector($form_state)->buildSelectorForm([], $form_state);
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $payment_status->getDescription(),
      '#maxlength' => 255,
    );

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->getParentPaymentStatusSelector($form_state)->validateSelectorForm($form['parent_id'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getParentPaymentStatusSelector($form_state)->submitSelectorForm($form['parent_id'], $form_state);
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $payment_status, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\payment\Entity\PaymentStatusInterface $payment_status */
    parent::copyFormValuesToEntity($payment_status, $form, $form_state);
    $values = $form_state->getValues();
    $payment_status->setId($values['id']);
    $payment_status->setLabel($values['label']);
    $selected_parent = $this->getParentPaymentStatusSelector($form_state)->getSelectedPlugin();
    $payment_status->setParentId($selected_parent ? $selected_parent->getPluginId() : NULL);
    $payment_status->setDescription($values['description']);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $payment_status = $this->getEntity();
    $payment_status->save();
    $this->messenger()->addMessage($this->t('@label has been saved.', array(
      '@label' => $payment_status->label()
    )));
    $form_state->setRedirect('entity.payment_status.collection');
  }

  /**
   * Checks if a payment status with a particular ID already exists.
   *
   * @param string $id
   *
   * @return bool
   */
  public function paymentStatusIdExists($id) {
    return (bool) $this->paymentStatusStorage->load($id);
  }

  /**
   * Gets the parent payment status selector.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface
   */
  protected function getParentPaymentStatusSelector(FormStateInterface $form_state) {
    $key = 'parent_payment_status_selector';
    if ($form_state->has($key)) {
      $plugin_selector = $form_state->get($key);
    }
    else {
      $plugin_selector = $this->pluginSelectorManager->createInstance('payment_select_list');
      $plugin_selector->setSelectablePluginType($this->paymentStatusPluginType);
      $plugin_selector->setCollectPluginConfiguration(FALSE);
      $plugin_selector->setLabel($this->t('Parent status'));

      $form_state->set($key, $plugin_selector);
    }

    return $plugin_selector;
  }

}
