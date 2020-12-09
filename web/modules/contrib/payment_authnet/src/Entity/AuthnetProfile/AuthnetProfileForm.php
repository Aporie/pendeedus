<?php

namespace Drupal\payment_authnet\Entity\AuthnetProfile;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Drupal\plugin\PluginType\PluginTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Mollie profile add/edit form.
 */
class AuthnetProfileForm extends EntityForm {

  /**
   * The Mollie profile storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $authnetProfileStorage;

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface
   */
  protected $pluginSelectorManager;

  /**
   * The plugin type manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManager
   */
  protected $pluginTypeManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Entity\EntityStorageInterface $authnet_profile_storage
   *   The Authnet profile storage.
   * @param \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface $plugin_selector_manager
   *   The plugin selector manager.
   * @param \Drupal\plugin\PluginType\PluginTypeManager $plugin_type_manager
   *   The plugin type manager.
   */
  public function __construct(TranslationInterface $string_translation, EntityStorageInterface $authnet_profile_storage, PluginSelectorManagerInterface $plugin_selector_manager, PluginTypeManager $plugin_type_manager) {
    $this->authnetProfileStorage = $authnet_profile_storage;
    $this->pluginSelectorManager = $plugin_selector_manager;
    $this->pluginTypeManager = $plugin_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($container->get('string_translation'), $entity_type_manager->getStorage('authnet_profile'), $container->get('plugin.manager.plugin.plugin_selector'), $container->get('plugin.plugin_type_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $maxlength = ['#maxlength' => 255];
    $form['label'] = $this->formConfigField($this->t('Label'), $entity->label()) + $maxlength;
    $form['id'] = [
      '#type' => 'machine_name',
      '#disabled' => !$entity->isNew(),
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$this, 'AuthnetProfileIdExists'],
      ],
    ] + $this->formConfigField('', $entity->id()) + $maxlength;

    $sandbox_url = [
      ':url' => Url::fromUri('https://sandbox.authorize.net/')->toString(),
    ];

    $form['apiId'] = $this->formConfigField($this->t('API Login ID'), $entity->getApiId(), TRUE, $this->t('For sandbox profile you can obtain both API Login ID and Transaction key at "API Credentials & Keys" section at <a href=":url">sandbox.authorize.net</a>.', $sandbox_url)) + $maxlength;
    $form['apiTransactionKey'] = $this->formConfigField($this->t('Transaction Key'), $entity->getApiTransactionKey()) + $maxlength;
    $form['apiKey'] = $this->formConfigField($this->t('API key'), $entity->getApiKey(), FALSE, $this->t('It is also used in transactions as a prefix to transaction ID. If empty, <em>ref</em> prefix will be used.')) + [
      '#maxlength' => 10,
      '#size' => 10,
    ];
    $sandbox_default_value = is_null($entity->getSandboxMode()) ? TRUE : (bool) $entity->getSandboxMode();
    $form['sandboxMode'] = $this->formConfigField($this->t('Sandbox Mode'), $sandbox_default_value, FALSE, $this->t('Must be enabled for <a href=":url">Sandbox.Authorize.net</a> environment.', $sandbox_url));

    return parent::form($form, $form_state);
  }

  /**
   * Helper function to render authnet profile form.
   *
   * @param string $title
   *   Form API field Title.
   * @param mixed $default_value
   *   Form API field default value.
   * @param bool $required
   *   Form API required property.
   * @param string $description
   *   Form API field description.
   *
   * @see \Drupal\payment_authnet\Entity\AuthnetProfile\AuthnetProfileForm::form()
   *
   * @return array
   *   Form API element array.
   */
  protected function formConfigField($title, $default_value, $required = TRUE, $description = '') {
    $type = 'textfield';
    if (is_bool($default_value)) {
      $type = 'checkbox';
    }
    if (empty($title)) {
      $type = 'machine_name';
    }
    return [
      '#type' => $type,
      '#title' => $title,
      '#default_value' => $default_value,
      '#required' => $required,
      '#description' => $description,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $authnet_profile, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\payment_authnet\Entity\MolliePaymentInterface $mollie_profile */
    parent::copyFormValuesToEntity($authnet_profile, $form, $form_state);
    $values = $form_state->getValues();
    $authnet_profile->setId($values['id']);
    $authnet_profile->setLabel($values['label']);
    $authnet_profile->setApiId($values['apiId']);
    $authnet_profile->setApiTransactionKey($values['apiTransactionKey']);
    $authnet_profile->setApiKey($values['apiKey']);
    $authnet_profile->setSandboxMode($values['sandboxMode']);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $authnet_profile = $this->getEntity();
    $authnet_profile->save();
    $this->messenger()->addStatus($this->t('@label has been saved.', [
      '@label' => $authnet_profile->label(),
    ]));
    $form_state->setRedirect('entity.authnet_profile.collection');
  }

  /**
   * Checks if a Authnet profile with a particular ID already exists.
   *
   * @param string $id
   *   Authnet profile ID.
   *
   * @return bool
   *   TRUE if Authorize net profile exists, FALSE otherwise.
   */
  public function authnetProfileIdExists($id) {
    return (bool) $this->authnetProfileStorage->load($id);
  }

}
