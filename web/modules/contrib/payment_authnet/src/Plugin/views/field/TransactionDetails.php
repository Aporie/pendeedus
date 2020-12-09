<?php

namespace Drupal\payment_authnet\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\payment_authnet\Plugin\Payment\Method\AuthnetInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to get the transaction details for Payment Authnet method.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("payment_authnet_transaction_details")
 */
class TransactionDetails extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $si = 'substring_index';
    $s = 'substring';
    $r = 'regexp';
    $keys = explode('__', $this->options['payment_authnet_display']);
    $k = array_pop($keys);
    $f = $this->tableAlias . '.' . $this->realField;
    // Symbol ';' is is prohibited in Drupal Query. Replace it by hex value.
    $uh = 'unhex(\'3B\')';
    $ct = 'concat';
    $is_string = "$f $r '\"$k\".s:[0-9]+'";
    $string_val = "$si($si($si($f, $ct('\"$k\"', $uh, 's:'), -1), $ct('\"', $uh), 1), ':\"', -1)";
    $number_val = "$s($si($s($si($f, $ct('\"$k\"', $uh), -1), 3), $uh, 1), $f $r '\"$k\".(d|i):\-?[0-9]+')";
    $formula = "convert(if($is_string, $string_val, $number_val) using utf8)";
    $alias = $this->tableAlias . '_authnet_' . $k;
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->field_alias = $this->query->addField(NULL, $formula, $alias, $params);
  }

  /**
   * Define the available options.
   *
   * @return array
   *   An array with options and default values.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['payment_authnet_display'] = ['default' => 'card_type'];

    return $options;
  }

  /**
   * The list of options (what properties to display) for Options form.
   *
   * @return array
   *   Associative array with options (available transaction details).
   */
  public static function getTransactionDetailsDisplayOptionsList() {
    return [
      'credit_card_number' => t('Last 4 digits of Credit card number'),
      'transactionId' => t('Authorize.net last request Transaction ID'),
      'expiration_date' => t('Credit Card Expiration Date'),
      'card_type' => t('Card Type'),
      'email' => t("The customer's valid email address"),
      'card_code_response' => t('Card code verification (CCV) response'),
      'transaction_status' => t('The status of the transaction'),
      'transaction_type' => t('The type of transaction that was originally submitted'),
      'response_code' => t('The overall status of the transaction'),
      'cavv_response' => t('Cardholder authentication verification response code'),
      'billing_information__first_name' => t('Billing information: First name'),
      'billing_information__last_name' => t('Billing information: Last name'),
      'invoice_number' => t('Invoice Number - usually it matches payment ID, but not always for refund payments.'),
    ];
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['payment_authnet_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Authorize.net transaction details to display'),
      '#default_value' => $this->options['payment_authnet_display'],
      '#description' => $this->t('Select What information you would like to display'),
      '#options' => $this->getTransactionDetailsDisplayOptionsList(),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $result = parent::render($values);
    // Update transaction Details if value is empty.
    if (empty($result)) {
      $payment_method = $values->_entity->getPaymentMethod();
      if ($payment_method instanceof AuthnetInterface) {
        $payment_method->getTransactionDetails();
      }
    }
    // TODO :: Replace with theme function.
    $method_name = $this->getFormatter();
    if ($method_name) {
      $result = call_user_func([$this, $method_name], $result);
    }
    return $result;
  }

  /**
   * Returns function to format DB values into human readable ones.
   *
   * @return string|bool
   *   Function name to call if function exists, FALSE otherwise.
   */
  protected function getFormatter() {
    $key = $this->options['payment_authnet_display'];
    $method_name = 'format' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));

    return method_exists($this, $method_name) ? $method_name : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    // Do not support sorting for fields, where real value is replaced by value
    // understandible by a human.
    return (bool) !$this->getFormatter() ?: FALSE;
  }

  /**
   * Convert Transaction CAVV response code into human readable string.
   *
   * @param string $code
   *   Cardholder authentication verification response code.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translated string with code explanation.
   */
  protected function formatCavvResponse($code = NULL) {
    $statuses = [
      '0' => $this->t('CAVV was not validated because erroneous data was submitted.'),
      '1' => $this->t('CAVV failed validation.'),
      '2' => $this->t('CAVV passed validation.'),
      '3' => $this->t('CAVV validation could not be performed; issuer attempt incomplete.'),
      '4' => $this->t('CAVV validation could not be performed; issuer system error.'),
      '5' => $this->t('Reserved for future use.'),
      '6' => $this->t('Reserved for future use.'),
      '7' => $this->t('CAVV failed validation, but the issuer is available. Valid for U.S.-issued card submitted to non-U.S acquirer.'),
      '8' => $this->t('CAVV passed validation and the issuer is available. Valid for U.S.-issued card submitted to non-U.S. acquirer.'),
      '9' => $this->t('CAVV failed validation, but the issuer is available. Valid for U.S.-issued card submitted to non-U.S acquirer.'),
      'A' => $this->t('CAVV passed validation but the issuer unavailable. Valid for U.S.-issued card submitted to non-U.S acquirer.'),
      'B' => $this->t('CAVV passed validation, information only, no liability shift.'),
    ];
    if (isset($statuses[(string) $code])) {
      return $statuses[(string) $code];
    }
    return $this->t('CAVV not validated');
  }

  /**
   * Convert Transaction response code into human readable string.
   *
   * @param string $code
   *   Response code (1..4)
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translated string with code explanation.
   */
  protected function formatResponseCode($code = NULL) {
    $response_codes = [
      '1' => $this->t('Approved'),
      '2' => $this->t('Declined'),
      '3' => $this->t('Error'),
      '4' => $this->t('Held for Review'),
    ];
    if (isset($response_codes[(string) $code])) {
      return $response_codes[(string) $code];
    }
    return $this->t('Unknown');
  }

  /**
   * Convert Transaction CCV code into human readable string.
   *
   * @param string $code
   *   One letter Card Code Verification Response.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translated string with code explanation.
   */
  protected function formatCardCodeResponse($code = NULL) {
    $card_code_responses = [
      'M' => $this->t('CVV matched.'),
      'N' => $this->t('CVV did not match.'),
      'P' => $this->t('CVV was not processed.'),
      'S' => $this->t('CVV should have been present but was not indicated.'),
      'U' => $this->t('The issuer was unable to process the CVV check.'),
    ];
    if (isset($card_code_responses[(string) $code])) {
      return $card_code_responses[(string) $code];
    }
    return $this->t('Unknown');
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    if (!empty($this->options['admin_label'])) {
      return $this->options['admin_label'];
    }
    $title = ($short && isset($this->definition['title short'])) ? $this->definition['title short'] : $this->definition['title'];
    return $this->t('@group: @title (@subtitle)', [
      '@group' => $this->definition['group'],
      '@title' => $title,
      '@subtitle' => $this->options['payment_authnet_display'],
    ]);
  }

}
