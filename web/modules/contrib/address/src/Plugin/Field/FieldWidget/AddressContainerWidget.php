<?php

namespace Drupal\address\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;

/**
 * Plugin implementation of the 'address_container' widget.
 *
 * @FieldWidget(
 *   id = "address_container",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "address"
 *   },
 * )
 */
class AddressContainerWidget extends AddressDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#type'] = 'container';
    return $element;
  }

}
