<?php

namespace Drupal\payment_authnet;

use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides helper functions for additional fields.
 */
trait AdditionalFieldsTrait {

  use StringTranslationTrait;

  /**
   * Returns the list of additional fields to be sent to Authorize.net.
   *
   * @return array
   *   The list of available / supported fields grouped by categories.
   */
  protected function getAvailableFields() {
    return [
      'billing_information' => [
        '#type' => 'details',
        '#title' => $this->t('Billing Information'),
        'first_name' => [
          '#type' => 'textfield',
          '#title' => $this->t('First Name'),
        ],
        'last_name' => [
          '#type' => 'textfield',
          '#title' => $this->t('Last Name'),
        ],
      ],
    ];
  }

  /**
   * Converts human readable string into machine name.
   *
   * @param string $human_readable
   *   Human readable string.
   *
   * @return string
   *   Machine name (allowed characters - lowercase letters, numbers and '_').
   */
  protected function humanToMachine($human_readable) {
    return preg_replace('@[^a-z0-9_]+@', '_', strtolower($human_readable));
  }

  /**
   * Builds section wrapper for additional fields.
   *
   * @param string $section_name
   *   Section system name.
   * @param array $section_details
   *   Section details.
   * @param array $config
   *   Field mapping config.
   *
   * @return array
   *   Form element wrapper array ('details' type).
   */
  protected function buildSectionWrapper($section_name, array $section_details, array $config = []) {
    $result[$section_name] = [
      '#type' => $section_details['#type'],
      '#title' => $config[$section_name]['section_title'] ?: $section_details['#title'],
      '#weight' => $config[$section_name]['section_position'] ?: '0',
      '#tree' => TRUE,
    ];
    if (!empty($config[$section_name]['section_description'])) {
      $result[$section_name]['#description'] = $config[$section_name]['section_description'];
    }
    return $result;
  }

  /**
   * Creates fields in addition to credit card number, date and cvv.
   *
   * @return array
   *   An array of additional fields to adjust Configuration Form.
   */
  protected function addAdditionalFields() {
    $result = [];
    $field_mapping = \Drupal::config('payment_authnet.settings')->get('field_mappings');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $options = ['clear' => TRUE, 'langcode' => $langcode];
    $availableFields = $this->getAvailableFields();
    foreach (Element::children($availableFields) as $section_name) {
      $result += $this->buildSectionWrapper($section_name, $availableFields[$section_name], $field_mapping);
      foreach (Element::children($availableFields[$section_name]) as $field_name) {
        $result[$section_name][$field_name] = [
          '#type' => $availableFields[$section_name][$field_name]['#type'],
          '#title' => $field_mapping[$section_name][$field_name]['title'],
          '#required' => $field_mapping[$section_name][$field_name]['required'],
          '#default_value' => $this->token->replace($field_mapping[$section_name][$field_name]['default_value'], [], $options),
        ];
        if ($field_mapping[$section_name][$field_name]['required']) {
          $result[$section_name]['#required'] = TRUE;
          if (empty($result[$section_name][$field_name]['#default_value'])) {
            $result[$section_name]['#open'] = TRUE;
          }
        }
      }
    }

    return $result;
  }

  /**
   * Additional fields default values for defaultConfiguration().
   *
   * @return array
   *   An array with additional fields default values.
   */
  protected function additionalFieldsDefaultConfiguration() {
    $result = [];
    $field_mapping = \Drupal::config('payment_authnet.settings')->get('field_mappings');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $options = ['clear' => TRUE, 'langcode' => $langcode];
    $availableFields = $this->getAvailableFields();
    foreach (Element::children($availableFields) as $section_name) {
      $result[$section_name]['section_title'] = $field_mapping[$section_name]['section_title'] ?: $availableFields[$section_name]['#title'];
      $result[$section_name]['section_description'] = $field_mapping[$section_name]['section_description'] ?: '';
      $result[$section_name]['section_position'] = $field_mapping[$section_name]['section_position'] ?: 0;
      foreach (Element::children($availableFields[$section_name]) as $field_name) {
        if (!empty($field_mapping[$section_name][$field_name]['title'])) {
          $result[$section_name][$field_name] = $this->token->replace($field_mapping[$section_name][$field_name]['default_value'], [], $options);
        }
      }
    }
    return $result;
  }

}
