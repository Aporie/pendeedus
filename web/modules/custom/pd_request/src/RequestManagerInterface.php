<?php

namespace Drupal\pd_request;

use Drupal\pd_request\Entity\DocRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface for request page service.
 */
interface RequestManagerInterface {

  /**
   * Get doc_request fee.
   * 
   * @param \Drupal\pd_request\Entity\DocRequest $doc_request
   *  The doc request entity.
   * @param int $coverage_type_id
   *  The coverage type taxonomy term id.
   * 
   * @return int
   *  The doc request amount.
   */
  public function getDocRequestFee(DocRequest $doc_request, $coverage_type_id = NULL);

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

  /**
   * Perform a payment before adding additional fee.
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *  The request.
   * @param \Drupal\pd_request\Entity\DocRequest $doc_request
   *  The doc request entity.
   */
  public function payAdditionalFee(Request $request, DocRequest $doc_request);
}
