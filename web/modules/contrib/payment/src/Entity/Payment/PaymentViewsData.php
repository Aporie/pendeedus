<?php

namespace Drupal\payment\Entity\Payment;

use Drupal\views\EntityViewsData;

/**
 * Provides generic views integration for entities.
 */
class PaymentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    unset($data['payment']['payment_method__plugin_configuration']);
    unset($data['payment']['payment_type__plugin_configuration']);

    $data['payment']['payment_method__plugin_id']['field'] = [
      'id' => 'payment_method_label',
    ];
    $data['payment']['payment_method__plugin_id']['filter'] = [
      'id' => 'payment_method',
    ];

    $data['payment']['payment_type__plugin_id']['field'] = [
      'id' => 'payment_type_label',
    ];

    $data['payment']['current_payment_status_delta'] = array(
      'title' => t('Current payment status'),
      'relationship' => array(
        'title' => t('Current payment status'),
        'base' => 'payment__payment_statuses',
        'base field' => 'delta',
        'id' => 'payment_current_status',
        'label' => t('Current payment status'),
      ),
    );

    $data['payment']['id_payment_statuses'] = array(
      'title' => t('Payment status'),
      'real field' => 'id',
      'relationship' => array(
        'title' => t('Payment statuses'),
        'base' => 'payment__payment_statuses',
        'base field' => 'entity_id',
        'id' => 'standard',
        'label' => t('Payment statuses'),
      ),
    );

    $data['payment']['id_payment_line_items'] = array(
      'title' => t('Line items'),
      'real field' => 'id',
      'relationship' => array(
        'title' => t('Line items'),
        'base' => 'payment__line_items',
        'base field' => 'entity_id',
        'id' => 'standard',
        'label' => t('Line items'),
      ),
    );

    $data['payment']['amount'] = array(
      'title' => t('Amount'),
      'real field' => 'id',
      'field' => [
        'id' => 'payment_amount',
      ],
    );

    $data['payment__line_items']['table']['group'] = $this->t('Payment line item');
    $data['payment__line_items']['table']['provider'] = $this->entityType->getProvider();

    $data['payment__line_items']['line_items_plugin_id'] = [
      'title' => t('Line item'),
      'field' => [
        'id' => 'payment_line_item_label',
      ],
    ];

    $data['payment__payment_statuses']['table']['group'] = $this->t('Payment status');
    $data['payment__payment_statuses']['table']['provider'] = $this->entityType->getProvider();

    $data['payment__payment_statuses']['payment_statuses_plugin_id'] = [
      'title' => t('Payment status'),
      'field' => [
        'id' => 'payment_status_label',
      ],
      'filter' => [
        'id' => 'payment_status',
      ],
    ];

    return $data;
  }

}
