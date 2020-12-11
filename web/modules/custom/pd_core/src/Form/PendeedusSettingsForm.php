<?php

namespace Drupal\pd_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to configure pendeedus custom settings.
 */
class PendeedusSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pd_core_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pd_core.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pd_core.settings');

    $form['dashboard'] = [
      '#type' => 'details',
      '#title' => $this->t('Dashboard options'),
      '#open' => TRUE,
    ];

    $form['dashboard']['#markup'] = $this->t('Configure custom message displayed to vendor and employee on login.');

    $form['dashboard']['employee_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message to employee'),
      '#description' => $this->t('Custom message displayed to employee on login.'),
      '#default_value' => $config->get('employee_message')['value'],
    ];

    $form['dashboard']['vendor_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message to vendor'),
      '#description' => $this->t('Custom message displayed to vendor on login.'),
      '#default_value' => $config->get('vendor_message')['value'],
    ];

    $form['documents'] = [
      '#type' => 'details',
      '#title' => $this->t('Documents'),
      '#open' => TRUE,
    ];

    $form['documents']['#markup'] = $this->t('Manage documents options.');

    $form['documents']['document_fee'] = [
      '#type' => 'number',
      '#min' => 0.0,
      '#title' => $this->t('Document fee ($)'),
      '#description' => $this->t('Fees charged for each document (USD).'),
      '#step' => 0.01,
      '#default_value' => $config->get('document_fee'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pd_core.settings')
      ->set('employee_message', $form_state->getValue('employee_message'))
      ->set('vendor_message', $form_state->getValue('vendor_message'))
      ->set('document_fee', $form_state->getValue('document_fee'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
