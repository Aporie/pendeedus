<?php

namespace Drupal\payment_authnet\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\payment_authnet\Plugin\views\field\TransactionDetails;

/**
 * Filters Payments by Payment Authnet Transaction Details.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("payment_authnet_transaction_details_filter")
 */
class TransactionDetailsFilter extends StringFilter {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['payment_authnet_key'] = ['default' => 'card_type'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['payment_authnet_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Authorize.net transaction details to filter by'),
      '#default_value' => $this->options['payment_authnet_key'],
      '#description' => $this->t('Select What information you would like to filter by'),
      '#options' => TransactionDetails::getTransactionDetailsDisplayOptionsList(),
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];
  }

  /**
   * Gets case insensitive MYSQL regex version of value.
   *
   * @param string $value
   *   Original value from user's input.
   *
   * @return string
   *   Case insensitive MYSQL regex version of value.
   */
  private function getValueForRegex($value) {
    $value_lower = strtolower($value);
    $value_upper = strtoupper($value);
    $result = '';
    for ($i = strlen($value_lower) - 1; $i >= 0; $i--) {
      // Skip braces from regexp value since they can break query.
      if ('"' !== $value_upper[$i] && "'" !== $value_upper[$i] && '`' !== $value_upper[$i]) {
        $result = (ctype_alpha($value_lower[$i]) ? '[' . $value_upper[$i] . $value_lower[$i] . ']' : $value_lower[$i]) . $result;
      }
    }
    return $result;
  }

  /**
   * Gets keys to filter by from configuration.
   *
   * @return array
   *   An array of keys of Authnet properties to filter by,
   *   selected in filter configuration form.
   */
  protected function getKeyForRegex() {
    $keys = (array) $this->options['payment_authnet_key'];
    foreach ($keys as &$key) {
      if (strpos($key, '__') !== FALSE) {
        $subkeys = explode('__', $key);
        $key = array_pop($subkeys);
      }
    }
    return $keys;
  }

  /**
   * An array of vars prepared for query operators callback.
   *
   * @param bool $direct_operator
   *   False if 'not regexp', true for 'regexp'.
   * @param string $value
   *   Original value from user's input.
   *
   * @return array
   *   An array of vars prepared for query operators callback
   *   (value, keys, where, operator).
   */
  protected function getVarsForBuildWhere($direct_operator = TRUE, $value = NULL) {
    $keys = $this->getKeyForRegex();
    $where = count($keys) > 1 && $direct_operator ? new Condition('OR') : new Condition('AND');
    return [
      $value ? $value : $this->getValueForRegex($this->value),
      $keys,
      $where,
      $direct_operator || $this->operator == '=' ? 'REGEXP' : 'NOT REGEXP',
    ];
  }

  /**
   * Builds where condition for query operators callback.
   *
   * @param string $field
   *   Field name for where condition.
   * @param string $value
   *   Sanitized value or empty to sanitize and use $this->value.
   * @param bool $direct_operator
   *   False if 'not regexp', true for 'regexp'.
   */
  protected function buildWhereRegexp($field, $value = NULL, $direct_operator = TRUE) {
    list($value, $keys, $where, $operator) = $this->getVarsForBuildWhere($direct_operator, $value);
    foreach ($keys as $key) {
      $where->condition($field, 's:' . strlen($key) . ':"' . $key . '"[[.semicolon.]](s|d|i):([0-9]+:")?' . $value . '"?[[.semicolon.]]', $operator);
    }
    $this->query->addWhere($this->options['group'], $where);
  }

  /**
   * Builds where condition for Equal operators.
   *
   * @param string $field
   *   Field name for where condition.
   */
  public function opEqual($field) {
    $this->buildWhereRegexp($field, NULL, FALSE);
  }

  /**
   * Builds where condition for contain operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opContains($field) {
    $this->buildWhereRegexp($field, '[^[.semicolon.]]*' . $this->getValueForRegex($this->value) . '[^[.semicolon.]]*');
  }

  /**
   * Generates an array of all possible combinations of words.
   *
   * @param array $elements
   *   The list of words to use for generation unique combinations.
   */
  protected function permutations(array $elements) {
    if (count($elements) <= 1) {
      yield $elements;
    }
    else {
      foreach ($this->permutations(array_slice($elements, 1)) as $permutation) {
        foreach (range(0, count($elements) - 1) as $i) {
          yield array_merge(array_slice($permutation, 0, $i), [$elements[0]], array_slice($permutation, $i));
        }
      }
    }
  }

  /**
   * Converts string with words into array of words for ContainsWord operator.
   *
   * @param string $value
   *   User's input string.
   *
   * @return array
   *   An array of words, filtered from user's input.
   */
  private function getWordsArrayForOpContainsWord($value) {
    $words_array = [];
    preg_match_all(static::WORDS_PATTERN, ' ' . $value, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      $phrase = FALSE;
      // Strip off phrase quotes.
      if ($match[2][0] === '"') {
        $match[2] = substr($match[2], 1, -1);
        $phrase = TRUE;
      }
      $words = trim($match[2], ',?!();:-');
      $words = $phrase ? [$words] : preg_split('/ /', $words, -1, PREG_SPLIT_NO_EMPTY);
      foreach ($words as $word) {
        $words_array[$word] = $this->getValueForRegex($word);
      }
    }
    return array_values($words_array);
  }

  /**
   * Builds where condition for ContainsWord operators.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opContainsWord($field) {
    // Don't filter on empty strings.
    if (empty($this->value)) {
      return;
    }

    $words_array = $this->getWordsArrayForOpContainsWord($this->value);

    if (empty($words_array)) {
      return;
    }

    if ($this->operator !== 'word') {
      $conditions_array = [];
      foreach ($this->permutations($words_array) as $permutation) {
        $conditions_array[] = implode('[^[.quotation-mark.]]+', $permutation);
      }
      $words_array = $conditions_array;
    }

    $this->buildWhereRegexp($field, '[^"]*(' . implode('|', $words_array) . ')[^"]*');
  }

  /**
   * Builds where condition for 'starts with' operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opStartsWith($field) {
    $this->buildWhereRegexp($field, $this->getValueForRegex($this->value) . '[^"]*');
  }

  /**
   * Builds where condition for 'not starts with' operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opNotStartsWith($field) {
    $this->buildWhereRegexp($field, $this->getValueForRegex($this->value) . '[^"]*', FALSE);
  }

  /**
   * Builds where condition for 'ends with' operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opEndsWith($field) {
    $this->buildWhereRegexp($field, '[^"]*' . $this->getValueForRegex($this->value));
  }

  /**
   * Builds where condition for 'not ends with' operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opNotEndsWith($field) {
    $this->buildWhereRegexp($field, '[^"]*' . $this->getValueForRegex($this->value), FALSE);
  }

  /**
   * Builds where condition for 'not like' operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opNotLike($field) {
    $this->buildWhereRegexp($field, '[^"]*' . $this->getValueForRegex($this->value) . '[^"]*', FALSE);
  }

  /**
   * Common code for 'Shorter than' and 'LongerThan' operators.
   *
   * @param string $field
   *   Field name for where condition.
   * @param string $operator
   *   Mysql comparison operator - '>' or '<'.
   */
  private function opShorterLongerThan($field, $operator) {
    $placeholder = $this->placeholder();
    // Does not work for multiple keys.
    $key = reset($this->getKeyForRegex());
    $key_length = strlen($key) + 5;

    $condition = "CONVERT(substring(substring(substring({$field}, locate('{$key}\";s:', {$field})), {$key_length}), 1, locate(':', substring(substring({$field}, locate('{$key}\";s:', {$field})), {$key_length})) - 1) USING utf8)";
    // Type cast the argument to an integer because the SQLite database driver
    // has to do some specific alterations to the query base on that data type.
    $this->query->addWhereExpression($this->options['group'], "$condition $operator $placeholder", [$placeholder => (int) $this->value]);
  }

  /**
   * Builds where condition for 'shorter than' operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opShorterThan($field) {
    $this->opShorterLongerThan($field, '<');
  }

  /**
   * Builds where condition for 'longer than' operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opLongerThan($field) {
    $this->opShorterLongerThan($field, '>');
  }

  /**
   * Builds where condition for 'empty' operator.
   *
   * @param string $field
   *   Field name for where condition.
   */
  protected function opEmpty($field) {
    list(, $keys,, $operator) = $this->getVarsForBuildWhere($this->operator != 'empty');
    // Does not work for multiple keys.
    $key = reset($keys);
    $this->query->addWhere($this->options['group'], $field, 's:' . strlen($key) . ':"' . $key . '"[[.semicolon.]](s|d|i):-?[0-9\.]+(:"[^"]+")?[[.semicolon.]]', $operator);
  }

}
