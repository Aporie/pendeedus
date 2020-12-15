<?php

namespace Drupal\pd_request;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\pd_request\Entity\DocRequest;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
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
      'store_id' => $store->id(),
      'created' => REQUEST_TIME,
      'placed' => REQUEST_TIME,
      'state' => 'draft',
    ]);
    $order->save();
    $order->addItem($order_item);
    $order->save();

    return $order;
  }

  /**
   * {@inheritdoc}
   */
  public function performPayment(DocRequest $doc_request) {
    $order = $doc_request->getOrder();
    $payment_gateway = $this->entityTypeManager->getStorage('commerce_payment_gateway')->load('pendeedus');

    // Get doc request author and payment method.
    $user_uid = $order->getCustomerId();
    $payment_methods = $this->entityTypeManager->getStorage('commerce_payment_method')->loadByProperties([
      'uid' => $user_uid,
      'reusable' => TRUE,
    ]);
    // @TODO select default payment method.
    $payment_method = reset($payment_methods);

    // Execute payment.
    $authnet = $payment_gateway->getPlugin();
    $payment = Payment::create([
      'payment_gateway' => $payment_gateway->id(),
      'order_id' => $order->id(),
      'payment_gateway_mode' => $authnet->getMode(),
      'payment_method' => $payment_method->id(),
      'remote_id' => $payment_method->getRemoteId(),
      'expires' => 0,
      'state' => 'new',
      'amount' => $order->getTotalPrice(),
      'uid' => $user_uid,
    ]);
    $payment->save();
    $authnet->createPayment($payment);
    // Set payment and order as completed.
    $payment->setState('completed');
    $payment->save();
    $order->setBillingProfile($payment_method->getBillingProfile());
    $order->getState()->applyTransitionById('place');
    $order->save();
    // Set doc request as completed.
    $doc_request->set('field_workflow', 'doc_request_completed');
    $doc_request->save();
  }

}
