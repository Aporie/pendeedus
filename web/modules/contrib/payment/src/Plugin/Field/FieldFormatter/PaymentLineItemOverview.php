<?php

namespace Drupal\payment\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\payment\LineItemCollection;
use Drupal\payment\LineItemCollectionInterface;

/**
 * A payment line item field formatter.
 *
 * @FieldFormatter(
 *   id = "payment_line_item_overview",
 *   label = @Translation("Overview"),
 *   field_types = {
 *     "plugin:payment_line_item",
 *   }
 * )
 */
class PaymentLineItemOverview extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $payment_line_items = new LineItemCollection();
    /** @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface $item */
    foreach ($items as $delta => $item) {
      $payment_line_items->setLineItem($item->getContainedPluginInstance());
    }
    $entity = $items->getEntity();
    if ($entity instanceof LineItemCollectionInterface) {
      $payment_line_items->setCurrencyCode($entity->getCurrencyCode());
    }
    $build[0] = array(
      '#payment_line_items' => $payment_line_items,
      '#type' => 'payment_line_items_display',
    );

    return $build;
  }

}
