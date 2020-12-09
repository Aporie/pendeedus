<?php

namespace Drupal\payment;

use Drupal\payment\Response\ResponseInterface;

/**
 * Provides an operation result.
 */
class OperationResult implements OperationResultInterface {

  /**
   * The response.
   *
   * @var \Drupal\payment\Response\ResponseInterface|null
   */
  protected $response;

  /**
   * Creates a new instance.
   *
   * @param \Drupal\payment\Response\ResponseInterface|null $response
   *   A response or NULL if the operation is completed.
   */
  public function __construct(ResponseInterface $response = NULL) {
    $this->response = $response;
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted() {
    return is_null($this->response);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionResponse() {
    return $this->response;
  }

}
