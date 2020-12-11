<?php

namespace Drupal\pd_request;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\pd_request\Entity\DocRequest;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service for request page.
 */
class RequestManager implements RequestManagerInterface {

   /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

    /**
   * {@inheritdoc}
   */
  public function createOrder(DocRequest $doc_request) {
    $store = $this->entityTypeManager->getStorage('commerce_store')->loadDefault();
    // Create a new order item.
    $order_item = OrderItem::create([
      'type' => 'document',
      'purchased_entity' => $doc_request,
      'quantity' => 1,
      'unit_price' => new Price($this->configFactory->get('pd_core.settings')->get('document_fee'), 'USD'),
    ]);
    $order_item->save();
    // Add the product variation to a new order.
    $order = Order::create([
      'type' => 'document_request',
      'order_items' => [$order_item],
      'store_id' => $store->id(),
      'created' => REQUEST_TIME,
      'placed' => REQUEST_TIME,
    ]);
    $order->save();

    return $order;
  }


}
