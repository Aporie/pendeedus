<?php

namespace Drupal\pd_request\Entity;

use Drupal\eck\Entity\EckEntity;
use Drupal\eck\EckEntityInterface;
use Drupal\core\Url;
use Drupal\commerce_price\Price;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the ECK entity.
 *
 * @ingroup eck
 */
class DocRequest extends EckEntity implements EckEntityInterface, PurchasableEntityInterface {
  
  use StringTranslationTrait;

   /**
   * {@inheritdoc}
   */
  public function setStores(array $stores) {
    $this->set('stores', $stores);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return $this->get('stores');
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreIds() {
    $store_ids = [];
    foreach ($this->get('stores') as $store_item) {
      $store_ids[] = $store_item->target_id;
    }
    return $store_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    return 'document';
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTitle() {
    $label = $this->label();
    if (!$label) {
      $label = $this->generateTitle();
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getSku() {
    return $this->get('sku')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSku($sku) {
    $this->set('sku', $sku);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    if (!$this->get('price')->isEmpty()) {
      return $this->get('price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice(Price $price) {
    $this->set('price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrder(Order $order) {
    $this->set('order', $order->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderId($order_id) {
    $this->set('order', $order_id);
    return $this;
  }

  /**
   * Generates the document request title.
   *
   * @return string
   *   The generated value.
   */
  protected function generateTitle() {
    if (!$this->id()) {
      // Title generation is not possible before the parent product is known.
      return '';
    }

    return $this->t('Document request N°@id', [
      '@id' => $this->id(),
      //'@url' => Url::fromRoute('entity.doc_request.canonical', ['doc_request' => $this->id()])->toString(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['stores'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Stores'))
      ->setDescription(t('The product stores.'))
      ->setRequired(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setDescription(t('The unique, machine-readable identifier for the document request.'))
      ->setRequired(TRUE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Price'))
      ->setDescription(t('The price'))
      ->setRequired(TRUE)
      ->setDefaultValueCallback('Drupal\pd_request\Entity\DocRequest::getDefaultPrice')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    
    $fields['order'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The attached order.'))
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setSetting('target_type', 'commerce_order')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for price base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return \Drupal\commerce_price\Price;
   *   The default price for ducument.
   */
  public static function getDefaultPrice() {
    $price = \Drupal::configFactory()->get('pd_core.settings')->get('document_fee');
    
    return [new Price($price ?: 0, 'USD')];
  }

}
