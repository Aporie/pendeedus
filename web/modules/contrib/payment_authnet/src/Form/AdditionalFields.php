<?php

namespace Drupal\payment_authnet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\payment_authnet\AdditionalFieldsTrait;

/**
 * Defines a form to field mapping for payment authnet.
 */
class AdditionalFields extends ConfigFormBase {

  use AdditionalFieldsTrait;

  const SETTINGS = 'payment_authnet.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_authnet_field_mapping';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $field_mapping = $this->config(self::SETTINGS)->get('field_mappings');

    $availableFields = $this->getAvailableFields();
    foreach (Element::children($availableFields) as $section_name) {
      $form += $this->buildSectionWrapperForm($section_name, $availableFields[$section_name], $field_mapping);
      foreach (Element::children($availableFields[$section_name]) as $field_name) {
        $form[$section_name] += $this->buildSectionForm($field_name, $availableFields[$section_name][$field_name]['#type'], $field_mapping[$section_name][$field_name]);
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Builds section wrapper for additional fields.
   *
   * @param string $section_name
   *   Section system name.
   * @param array $section_config
   *   Section config.
   * @param array $config
   *   Field mapping config.
   *
   * @return array
   *   Form element wrapper array ('details' type).
   */
  protected function buildSectionWrapperForm($section_name, array $section_config, array $config = []) {
    $result[$section_name] = [
      '#type' => $section_config['#type'],
      '#title' => $section_config['#title'],
      '#tree' => TRUE,
    ];
    $result[$section_name]['section_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section Title'),
      '#default_value' => $config[$section_name]['section_title'] ?: $section_config['#title'],
      '#maxlength' => 100,
    ];
    $result[$section_name]['section_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section Description'),
      '#default_value' => $config[$section_name]['section_description'] ?: '',
    ];
    $result[$section_name]['section_position'] = [
      '#type' => 'number',
      '#title' => $this->t('Section Position (weight)'),
      '#default_value' => $config[$section_name]['section_position'] ?: '0',
      '#min' => -100,
      '#max' => 100,
    ];
    return $result;
  }

  /**
   * Builds the form section for additional fields.
   *
   * @param string $field_name
   *   Field machine name.
   * @param string $type
   *   Field Type (form API).
   * @param array $field_config
   *   An array with field config ('title', 'default_value', 'required').
   *
   * @return array
   *   Form elements array.
   */
  protected function buildSectionForm($field_name, $type, array $field_config) {
    $elements = [];
    $elements[$field_name] = [
      '#type' => 'details',
      '#title' => $field_name,
      '#open' => $field_config['default_value'] ? TRUE : FALSE,
    ];
    $elements[$field_name]['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Title'),
      '#default_value' => isset($field_config['title']) ? $field_config['title'] : '',
      '#description' => $this->t('Leave this field empty to exclude it from payment form'),
    ];
    $elements[$field_name]['default_value'] = [
      '#type' => $type,
      '#title' => $this->t('Default Value'),
      '#default_value' => isset($field_config['default_value']) ? $field_config['default_value'] : '',
      '#description' => $this->t('Leave this empty to let the user enter own value every time.'),
    ] + $this->getTokenDescription();
    if ($elements[$field_name]['#open']) {
      $elements['#open'] = TRUE;
    }
    $elements[$field_name]['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => isset($field_config['required']) ? $field_config['required'] : FALSE,
    ];
    return $elements;
  }

  /**
   * Creates element array keys with token suggestions.
   *
   * @param array $types
   *   The list of allowed token types. If empty, all global types will
   *   be allowed.
   *
   * @return $this
   */
  protected function setTokenDescription(array $types = []) {
    sort($types);
    $key = md5(serialize($types));
    $this->tokenDescription[$key] = [];
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $token_tree = [
        '#theme' => 'token_tree_link',
        '#token_types' => $types,
        '#global_types' => count($types) ? FALSE : TRUE,
      ];
      $this->tokenDescription[$key] = [
        '#field_suffix' => $this->t('This field supports tokens. @browse_tokens_link', [
          '@browse_tokens_link' => \Drupal::service('renderer')->render($token_tree),
        ]),
        '#element_validate' => ['token_element_validate'],
        '#token_types' => $types,
      ];
    }

    return $this;
  }

  /**
   * Returns form element array with token suggestions.
   *
   * @param array $types
   *   The list of allowed token types. If empty, all global types will
   *   be allowed.
   *
   * @return array
   *   Array to join with element with tokens support.
   */
  public function getTokenDescription(array $types = [
    'site',
    'random',
    'current-date',
    'current-user',
  ]) {
    sort($types);
    $key = md5(serialize($types));
    if (!isset($this->tokenDescription[$key])) {
      $this->setTokenDescription($types);
    }
    return $this->tokenDescription[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->config(self::SETTINGS)
      ->set('field_mappings', $form_state->getValues())
      ->save();

    parent::submitForm($form, $form_state);
  }

}
