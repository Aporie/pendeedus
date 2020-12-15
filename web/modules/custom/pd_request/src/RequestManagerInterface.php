<?php

namespace Drupal\pd_request;

use Drupal\pd_request\Entity\DocRequest;

/**
 * Provides an interface for request page service.
 */
interface RequestManagerInterface {

  /**
   * Creates a new order.
   * 
   * @param \Drupal\pd_request\Entity\DocRequest $doc_request
   *  The doc request entity.
   * 
   * @return Drupal\commerce_order\Entity\Order
   *  The commerce Order.
   */
  public function createOrder(DocRequest $doc_request);

  /**
   * Perform a payment operation.
   * 
   * @param \Drupal\pd_request\Entity\DocRequest $doc_request
   *  The doc request entity.
   */
  public function performPayment(DocRequest $doc_request);
}
