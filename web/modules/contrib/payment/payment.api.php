<?php

/**
 * @file Contains Payment hook documentation.
 */

/**
 * Alters payment line item plugin definitions.
 *
 * @param array[] $definitions
 *   Keys are plugin IDs. Values are plugin definitions.
 */
function hook_payment_line_item_alter(array &$definitions) {
  // Remove a plugin entirely.
  unset($definitions['foo_plugin_id']);

  // Replace a plugin's class with another.
  $definitions['foo_plugin_id']['class'] = 'Drupal\foo\FooPlugin';
}

/**
 * Alters payment method plugin definitions.
 *
 * @param array[] $definitions
 *   Keys are plugin IDs. Values are plugin definitions.
 */
function hook_payment_method_alter(array &$definitions) {
  // Remove a plugin entirely.
  unset($definitions['foo_plugin_id']);

  // Replace a plugin's class with another.
  $definitions['foo_plugin_id']['class'] = 'Drupal\foo\FooPlugin';
}

/**
 * Alters payment method configuration plugin definitions.
 *
 * @param array[] $definitions
 *   Keys are plugin IDs. Values are plugin definitions.
 */
function hook_payment_method_configuration_alter(array &$definitions) {
  // Remove a plugin entirely.
  unset($definitions['foo_plugin_id']);

  // Replace a plugin's class with another.
  $definitions['foo_plugin_id']['class'] = 'Drupal\foo\FooPlugin';
}

/**
 * Alters payment status plugin definitions.
 *
 * @param array[] $definitions
 *   Keys are plugin IDs. Values are plugin definitions.
 */
function hook_payment_status_alter(array &$definitions) {
  // Remove a plugin entirely.
  unset($definitions['foo_plugin_id']);

  // Replace a plugin's class with another.
  $definitions['foo_plugin_id']['class'] = 'Drupal\foo\FooPlugin';
}

/**
 * Alters payment type plugin definitions.
 *
 * @param array[] $definitions
 *   Keys are plugin IDs. Values are plugin definitions.
 */
function hook_payment_type_alter(array &$definitions) {
  // Remove a plugin entirely.
  unset($definitions['foo_plugin_id']);

  // Replace a plugin's class with another.
  $definitions['foo_plugin_id']['class'] = 'Drupal\foo\FooPlugin';
}
