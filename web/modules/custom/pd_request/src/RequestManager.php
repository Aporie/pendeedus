<?php

namespace Drupal\pd_request;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\pd_coverage\CoverageAreaPriceManagerInterface;
use Drupal\pd_request\Controller\ViewDocumentsRefreshController;
use Drupal\pd_request\Entity\DocRequest;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Symfony\Component\HttpFoundation\Request;
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
   * The coverage area price manager.
   *
   * @var \Drupal\pd_coverage\CoverageAreaPriceManagerInterface
   */
  protected $coverageAreaPriceManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, CoverageAreaPriceManagerInterface $coverage_area_price_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->coverageAreaPriceManager = $coverage_area_price_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('pd_coverage.coverage_area_price_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDocRequestFee(DocRequest $doc_request, $coverage_type_id = NULL) {
    return $coverage_type_id ?
    $this->coverageAreaPriceManager->getDocRequestAmount($doc_request, $coverage_type_id) :
    $this->configFactory->get('pd_core.settings')->get('document_fee');
  }

  /**
   * {@inheritdoc}
   */
  public function createOrder(DocRequest $doc_request) {
    $store = $this->entityTypeManager->getStorage('commerce_store')->loadDefault();
    $price = $this->getDocRequestFee($doc_request);
    // Create a new order item.
    $order_item = OrderItem::create([
      'type' => 'document',
      'purchased_entity' => $doc_request,
      'quantity' => 1,
      'unit_price' => $price,
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
    $order->setOrderNumber('Req-' . $order->id());
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
    /** @var Drupal\commerce_authnet\Plugin\Commerce\PaymentGateway\AcceptJs $authnet */
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
  }

  /**
   * {@inheritdoc}
   */
  public function payAdditionalFee(Request $request, DocRequest $doc_request) {
    $doc_request->set('field_workflow', DocRequestStateManager::STATE_ACCEPTED_FEE);
    
    // If we use covegare pricing, update order.
    if ($doc_request->get('field_coverage_type')->getValue()) {
      $order = $doc_request->getOrder();
      $order_items = $order->getItems();
      $order_item = reset($order_items);
      $coverage_type_id = $doc_request->get('field_coverage_type')->getValue()[0]['target_id'];
      $amount = $this->getDocRequestFee($doc_request, $coverage_type_id);
      $order_item->setUnitPrice(new Price($amount, 'USD'), TRUE);
      $order_item->save();
      $order->recalculateTotalPrice();
      $order->save();
    }
    
    $this->performPayment($doc_request);
    $doc_request->save();
    $response = new ViewDocumentsRefreshController(\Drupal::service('renderer'));

    return $response->view();
  }

}
