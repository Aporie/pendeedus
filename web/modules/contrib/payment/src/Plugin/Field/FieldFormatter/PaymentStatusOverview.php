<?php

namespace Drupal\payment\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * A payment status field formatter.
 *
 * @FieldFormatter(
 *   id = "payment_status_overview",
 *   label = @Translation("Overview"),
 *   field_types = {
 *     "plugin:payment_status",
 *   }
 * )
 */
class PaymentStatusOverview extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $payment_statuses = [];
    /** @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface $item */
    foreach ($items as $delta => $item) {
      $payment_statuses[$delta] = $item->getContainedPluginInstance();
    }
    $build[0] = [
      '#payment_statuses' => $payment_statuses,
      '#type' => 'payment_statuses_display',
    ];

    return $build;
  }

}
