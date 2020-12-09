<?php

/**
 * @file
 * Provides views data for payment_authnet.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function payment_authnet_views_data_alter(array &$data) {
  $data['payment']['payment_authnet_transaction_details'] = [
    'title' => t('Authnet Transaction Details'),
    'field' => [
      'title' => t('Authnet Transaction Details'),
      'help' => t('Gets Payment Authnet Transaction Details.'),
      'id' => 'payment_authnet_transaction_details',
      'real field' => 'payment_method__plugin_configuration',
      'title short' => t('Authnet'),
    ],
    'filter' => [
      'title' => t('Authnet Transaction Details Filter'),
      'help' => t('Filter Payments by Payment Authnet Transaction Details.'),
      'id' => 'payment_authnet_transaction_details_filter',
      'field' => 'payment_method__plugin_configuration',
    ],
  ];
}
